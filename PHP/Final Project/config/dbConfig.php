<?php
    $host = "localhost";
    $dbName = "final";
    $userName = "root";
    $password = "";
    try {
        $conn = new PDO("mysql:host={$host}; dbname={$dbName};", $userName, $password);
    } catch (Exception $e) {
        if (isset($outputJSON)) {
            echo "{\"type\": \"error\", \"message\": \"An error occurred! " . $e->getMessage()."\"";
        } else {
            echo "Error: ".$e->getMessage();
        }
        return;
    }
?>