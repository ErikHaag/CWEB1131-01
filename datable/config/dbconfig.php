<?php

$host = "localhost";
$dbName = "datable";
$userName = "root";
$password = "";
try {
    $conn = new PDO("mysql:host={$host}; dbname={$dbName};", $userName, $password);
} catch (Exception $e) {
    if (isset(($outputJSON))) {
        echo "{\"type\": \"error\", \"message\": \"An error occurred! " . $e->getMessage()."\"";
    } else {
        echo "Error: ".$e->getMessage();
    }
    return;
}

$regexes = [
    "address" => "/^[\d\w\-. ]{2,65535}$/",
    "email" => "/^[\w._]+@[\w._]+\.[\w]{2,6}$/",
    "full_name" => "/^[A-Za-z ]+$/",
    "name" => "/^[A-Z][a-z]*$/",
    "password" => "/^[!-~]{5,50}$/",
    "pos_int" => "/^[1-9]\d*$/",
    "user_name" => "/^\w+$/"
];

function sanitize(&$inputs, $errorPrefix = "", $errorAffix = "")
{
    $errors = [];
    foreach ($inputs as $key => &$value) {
        $value = str_replace(";", "", $value);
        $value = htmlspecialchars($value);
        $value = stripslashes($value);
        $value = trim($value);
        if (strlen($value) == 0) {
            $errors[$errorPrefix . $key . $errorAffix] = $key . " was reduced to spaces!";
        }
    }
    return $errors;
}

function filter_assoc($subject, $keys)
{
    foreach ($subject as $key => $value) {
        if (!in_array($key, $keys)) {
            unset($subject[$key]);
        }
    }
    foreach ($keys as $key) {
        if (!array_key_exists($key, $subject)) {
            $subject[$key] = "";
        }
    }
    return $subject;
}

function get_role($id, $password) {
    global $conn, $regexes;
    $inputs = ["p" => $password];
    if (!empty(sanitize($inputs))) {
        return "";
    }
    if (!is_int($id) || $id < 1 || !is_string($inputs['p'])) {
        return "";
    }
    if (preg_match($regexes["password"], $inputs["p"]) != 1) {
        return "";
    }
    $query = "SELECT password, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    if (!$stmt->execute()) {
        return "";
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row == false) {
        return "";
    }
    if (!password_verify($inputs["p"], $row["password"])) {
        return "";
    }
    return $row["role"];
}

?>