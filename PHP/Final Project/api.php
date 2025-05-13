<?php
$outputJSON = true;
require "config/dbConfig.php";
require "utils.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $userData = [];
    foreach (["username", "password"] as $k) {
        $userData[$k] = $data[$k];
    }

    $errors = validate($userData, ["username", "password"], ["", ""]);

    if (!empty($errors)) {
        echo "{\"type\": \"error\", \"messages\": \"Invalid username or password\"}";
        http_response_code(401);
        exit();
    }

    [$id, $role] = get_role($conn, $userData["username"], $userData["password"]);

    if ($role == "none") {
        if ($id == -2) {
            echo "{type: \"error\", \"messages\": \"Unable to validate identity.\"}";
            // service unavailable
            http_response_code(503);
        } else {
            echo "{type: \"error\", \"messages\": \"Invalid username or password\"}";
            // unauthenticated
            http_response_code(401);
        }
        exit();
    }

    if (!is_bool($data["getSelf"])) {
        $errors[] = "getSelf must be a boolean";
    }



    // alias hell
    $countQuery = "SELECT COUNT(*) AS count";
    $rowQuery = "SELECT c.username AS username, c.email AS email, CONCAT(d.firstName, \" \", d.lastName) AS name, d.iconName AS icon";

    $queryCombiner = "FROM user_credentials AS c INNER JOIN user_details AS d ON c.id = d.userId";
    $queryExcludeUser = "WHERE d.userId <> :id";
    $queryOnlyUser = "WHERE d.userId = :id LIMIT 1";
    $queryModifiers = "";

    if ($data["getSelf"]) {
        if (!empty($errors)) {
            echo "{\"type\": \"searchError\", \"messages\": [\"" . implode("\", \"", $errors) . "\"]}";
            http_response_code(400);
            exit();
        }

        try {
            // user query
            $stmt = $conn->prepare("$rowQuery $queryCombiner $queryOnlyUser");
            $stmt->bindValue("id", $id, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception();
            }
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "{\"type\": \"success\", \"row\": " . json_encode($userRow) . "}";
        } catch (Exception $e) {
            echo "{\"type\": \"error\", \"messages\": [\"An error occurred!\"]}";
            http_response_code(503);
            exit();
        }
    } else {
        $sortInfo = [];
        foreach (["sortColumn", "sortDir", "query"] as $k) {
            $sortInfo[$k] = $data[$k];
        }

        $errors = validate($sortInfo, ["sortColumn", "sortDir", "query"], ["", "", ""]);

        if ($data["rows"] != 5 && $data["rows"] != 10 && $data["rows"] != 20) {
            $errors[] = "Invalid row count.";
        }

        if ($data["page"] < 0) {
            $errors[] = "Invalid page number";
        }

        if (!empty($errors)) {
            echo "{\"type\": \"searchError\", \"messages\": [\"" . implode("\", \"", $errors) . "\"]}";
            http_response_code(400);
            exit();
        }

        $hasQuery = true;
        if ($sortInfo["query"] == "") {
            $hasQuery = false;
        } else {
            switch ($sortInfo["query"][0]) {
                case "e":
                    $queryModifiers = " AND c.email LIKE :query";
                    break;
                case "n":
                    $queryModifiers = " AND CONCAT(d.firstName, \" \", d.lastName) LIKE :query";
                    break;
                default:
                    $queryModifiers = " AND c.username LIKE :query";
                    break;
            }
        }

        try {
            // Counting query
            $stmt = $conn->prepare("$countQuery $queryCombiner $queryExcludeUser$queryModifiers");
            $stmt->bindValue("id", $id, PDO::PARAM_INT);
            if ($hasQuery) {
                $stmt->bindValue("query", "%" . substr($sortInfo["query"], 2) . "%");
            }
            if (!$stmt->execute()) {
                throw new Exception();
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastPage = $row["count"];
            $perfectPage = ($lastPage % $data["rows"]) == 0;
            $lastPage = intdiv($lastPage, $data["rows"]);
            if ($perfectPage) {
                $lastPage--;
            }
            $lastPage = max($lastPage, 0);

            $data["page"] = min($data["page"], $lastPage);
            // results query
            $queryModifiers .= match ($sortInfo["sortColumn"]) {
                "email" => " ORDER BY c.email",
                "name" => " ORDER BY d.name",
                "username" => " ORDER BY c.username",
                default => " ORDER BY c.id"
            };

            $queryModifiers .= match ($sortInfo["sortDir"]) {
                "desc" => " DESC",
                default => " ASC"
            };

            $queryModifiers .= " LIMIT :rows OFFSET :page";
            $stmt = $conn->prepare("$rowQuery $queryCombiner $queryExcludeUser$queryModifiers");
            $stmt->bindValue("id", $id, PDO::PARAM_INT);
            if ($hasQuery) {
                $stmt->bindValue("query", "%" . substr($input["query"], 2) . "%");
            }
            $stmt->bindValue("rows", $data["rows"], PDO::PARAM_INT);
            $stmt->bindValue("page", $data["rows"] * $data["page"], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception();
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "{\"type\": \"success\", \"lastPage\": $lastPage, \"rows\": " . json_encode($results) . "}";

        } catch (Exception $e) {
            echo "{\"type\": \"error\", \"messages\": [\"An error occurred!\"]}";
            http_response_code(503);
            exit();
        }

    }
    // ok
    http_response_code(200);
}

?>