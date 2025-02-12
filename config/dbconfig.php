<?php

$host = "localhost";
$dbName = "itemborrowingdb";
$userName = "root";
$password = "";
try {
    $conn = new PDO("mysql:host={$host}; dbname={$dbName};", $userName, $password);
} catch (Exception $e) {
    echo "An error occurred! " . $e->getMessage();
}

function getData($c, $query) {
    $stmt = $c->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>