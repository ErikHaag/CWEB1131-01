<?php
$host = "localhost";
$dbName = "final";
$userName = "root";
$password = "";
try {
    $conn = new PDO("mysql:host={$host}; dbname={$dbName};", $userName, $password);
} catch (Exception $e) {
    if (isset($outputJSON)) {
        echo "{\"type\": \"error\", \"messages\": [\"Unable to connect to the database.\"]}";
    } else {
        echo "Unable to connect to the database.";
    }
    http_response_code(503);
    exit();
}
?>