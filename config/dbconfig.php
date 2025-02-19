<?php

$host = "localhost";
$dbName = "itemborrowingdb";
$userName = "root";
$password = "";
try {
    $conn = new PDO("mysql:host={$host}; dbname={$dbName};", $userName, $password);
} catch (Exception $e) {
    echo "{\"type\": \"error\", \"message\": \"An error occurred! " . $e->getMessage()."\"";
}

function getAllData($c, $query) {
    $stmt = $c->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function bindParams(&$stmt, $params) {
    foreach ($params as $i => $p) {
        $stmt->bindParam($i + 1, $p);
    }
}
?>