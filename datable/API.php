<?php
include "config/dbconfig.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // get the body of fetch()
    $requestBody = json_decode(file_get_contents('php://input'), true);
    switch ($requestBody["requestType"]) {
        case "getData":
            //ensure correct types
            if (!(is_string($requestBody["column"]) && is_string($requestBody["direction"]) && is_integer($requestBody["page"]) && is_integer($requestBody["rows"]) && is_string($requestBody["search"]))) {
                echo "{\"type\": \"error\", \"message\": \"Invalid variable types\"}";
                return;
            }
            //input vailidation
            if (preg_match("/^(id|name|email|address)$/", $requestBody["column"]) != 1) {
                echo "{\"type\": \"error\", \"message\": \"Invalid sorting column.\"}";
                return;
            }
            if (preg_match("/^(asc|desc)$/", $requestBody["direction"]) != 1) {
                echo "{\"type\": \"error\", \"message\": \"Invalid sorting direction.\"}";
                return;
            }
            if ($requestBody["page"] < 0) {
                echo "{\"type\": \"error\", \"message\": \"Invalid page number.\"}";
                return;
            }
            if ($requestBody["rows"] != 5 && $requestBody["rows"] != 10 && $requestBody["rows"] != 20) {
                echo "{\"type\": \"error\", \"message\": \"Invalid row quantity.\"}";
                return;
            }
            if (preg_match("/^(|(n:[A-Za-z]+( [A-Za-z]*)?)|e:([\w.-]+)?(@[\w.-]+)?(\.[A-Za-z]{2,6})?|a:[\w. ]+)$/", $requestBody["search"]) != 1) {
                echo "{\"type\": \"error\", \"message\": \"Invalid search query.\"}";
                return;
            }
            //we should be safe
            $query = "SELECT CUSTOMER_ID, STORE_ID, CONCAT(FIRST_NAME, \" \", LAST_NAME) AS FULL_NAME, EMAIL, ADDRESS FROM customers";
            if (strlen($requestBody["search"]) >= 3) {
                switch ($requestBody["search"][0]) {
                    case "n":
                        $query .= " WHERE CONCAT(FIRST_NAME, \" \", LAST_NAME) like \"%" . substr($requestBody["search"], 2) . "%\"";
                        break;
                    case "e":
                        $query .= " WHERE EMAIL like \"%" . substr($requestBody["search"], 2) . "%\"";
                        break;
                    case "a":
                        $query .= " WHERE ADDRESS like \"%" . substr($requestBody["search"], 2) . "%\"";
                        break;
                    default:
                        break;
                }
            }
            switch ($requestBody["column"]) {
                case "name":
                    $query .= " ORDER BY FULL_NAME " . ($requestBody["direction"] == "desc" ? "DESC" : "ASC");
                    break;
                case "email":
                    $query .= " ORDER BY EMAIL " . ($requestBody["direction"] == "desc" ? "DESC" : "ASC");
                    break;
                case "address":
                    $query .= " ORDER BY ADDRESS " . ($requestBody["direction"] == "desc" ? "DESC" : "ASC");
                    break;
                default:
                    $query .= " ORDER BY CUSTOMER_ID ASC";
                    break;
            }
            $query .= " LIMIT ? OFFSET ?";
            try {
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $requestBody["rows"], PDO::PARAM_INT);
                $stmt->bindValue(2, $requestBody["rows"] * $requestBody["page"], PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                echo "{\"type\": \"error\", \"message\": \"An unexpected error occured when try to execute the query.\n" . $e->getMessage() . "\"}";
                return;
            }
            // print_r($rows);
            foreach ($rows as &$row) {
                $row = "    {\"customerID\": " . $row["CUSTOMER_ID"] . ", \"storeID\": " . $row["STORE_ID"] . ", \"name\": \"" . $row["FULL_NAME"] . "\", \"email\": \"" . $row["EMAIL"] . "\", \"address\": \"" . $row["ADDRESS"] . "\"}";
            }
            echo "{\n";
            echo "  \"type\": \"success\",\n";
            echo "  \"data\": [\n";
            echo implode(",\n", $rows);
            echo "\n  ]\n";
            echo "}";
            break;
        case "deleteCustomer":
            if (is_int($requestBody["id"]) && $requestBody["id"] >= 1) {
                try {
                    $query = "DELETE FROM customers WHERE CUSTOMER_ID = ? LIMIT 1";
                    $stmt = $conn->prepare($query);
                    $stmt->bindValue(1, $requestBody["id"], PDO::PARAM_INT);
                    if ($stmt->execute()) {
                        echo "{\"type\": \"success\", \"message\": \"Customer has been successfully removed.\"}";
                    } else {
                        echo "{\"type\": \"error\", \"message\": \"An error occurred when processing your request.\"}";
                    }
                } catch (Exception $e) {
                    echo "{\"type\": \"error\", \"message\": \"An error occurred when processing your request.\"}";
                }
            } else {
                echo "{\"type\": \"error\", \"message\": \"Invalid ID.\"}";
            }
            break;
        default:
            echo "{\"type\": \"error\", \"message\": \"Invalid request type.\"}";
    }
} else {
    echo "{\"type\": \"error\", \"message\": \"You need to use the POST method.\"}";
}
?>