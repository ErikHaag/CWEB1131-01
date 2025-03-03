<?php

$host = "localhost";
$dbName = "itemborrowingdb";
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

function getAllData($c, $query) {
    $stmt = $c->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>