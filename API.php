<?php
include "config/dbconfig.php";


if ($_SERVER['REQUEST_METHOD'] == "GET") {
    //Get the data
    $users = getAllData($conn, "SELECT user_id, full_name FROM users");
    $items = getAllData($conn, "SELECT item_id, item_name FROM items");
    $borrows = getAllData($conn, "SELECT borrow_id, user_id, item_id, borrowed_date, due_date, status FROM borrowings");

    //convert {i->{id->j, name->s}, ...} to {j->s, ...} 
    $usersArray = [];
    foreach ($users as $u) {
        $usersArray[$u["user_id"]] = $u["full_name"];
    }
    $itemsArray = [];
    foreach ($items as $i) {
        $itemsArray[$i["item_id"]] = $i["item_name"];
    }

    //turn it into JSON
    $borrowObject = [];
    foreach ($borrows as $b) {
        if (!empty($usersArray[$b["user_id"]]) && !empty($itemsArray[$b["item_id"]])) {
            $borrow = "{";
            $borrow .= "\"borrowID\": " . $b["borrow_id"] . ",";
            $borrow .= "\"userName\": \"" . $usersArray[$b["user_id"]] . "\", ";
            $borrow .= "\"itemName\": \"" . $itemsArray[$b["item_id"]] . "\", ";
            $borrow .= "\"borrowedDate\": \"" . $b["borrowed_date"] . "\", ";
            $borrow .= "\"dueDate\": \"" . $b["due_date"] . "\", ";
            $borrow .= "\"status\": \"" . $b["status"] . "\"";
            $borrow .= "}";
            $borrowObject[] = $borrow;
        }
    }

    //echo the JSON for fetch()
    echo "{";
    echo "\"type\": \"success\",";
    echo "\"borrows\": [";
    echo implode(", ", $borrowObject);
    echo "] ";
    echo "}";
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {

    function sanitize(&$strings)
    {
        // use pass by reference modify the given array
        $errors = [];
        foreach ($strings as $key => &$value) {
            $value = str_replace(";", "", $value);
            $value = htmlspecialchars($value);
            $value = stripslashes($value);
            $value = trim($value);
            if (strlen($value) == 0) {
                $errors[$key] = "Was reduced to spaces!";
            }
        }
        unset($value);
        return $errors;
    }

    // get the body of fetch()
    $requestData = json_decode(file_get_contents('php://input'), true);
    $inputs = [];
    switch ($requestData["requestType"] ?? "") {
        case "deleteBorrow":
            $inputs["id"] = $requestData["id"] ?? "";
            $errors = sanitize($inputs);
            if (count($errors) > 0) {
                echo "{ \"type\": \"error\", \"message\": \"{$errors['id']}\"}";
                return;
            }
            if (preg_match("/^[1-9]\d*$/", $inputs["id"] ??"") != 1) {
                echo "{ \"type\": \"error\", \"message\": \"Invalid integer.\"";
                return;
            }
            try {
                $query = "DELETE FROM borrowings WHERE `borrow_id` = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $inputs["id"]);
                if ($stmt->execute()) {
                    echo "{\"type\": \"success\"}";
                } else {
                    echo "{\"type\": \"error\", \"message\": \"Couldn't delete row.\"}";
                }
            } catch (PDOException $e) {
                echo "{\"type\": \"error\", \"message\": \"".$e->getMessage()."\"}";
            } 
            break;
        default:
            echo "{ \"type\": \"error\", \"message\": \"Invalid request\" }";
            break;
    }
}
?>