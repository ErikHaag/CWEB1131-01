<?php

    function scrub(&$inputs) {
        foreach ($inputs as &$value) {
            $value = html_entity_decode($value);
            $value = stripslashes($value);
            $value = trim($value);
        }
    }

    function validate($inputs, $types, $names) {
        // code validation
        if (!is_array($inputs)) {
            throw new Exception("inputs must be an array");
        }
        if (!is_array($types)) {
            throw new Exception("types must be an array");
        }
        if (empty($inputs)) {
            throw new Exception("Given empty list");
        }
        if (count($inputs) != count($types)) {
            throw new Exception("inputs and entries have different sizes");
        }

        // real validation
        $errors = [];
        $i = 0;
        foreach ($inputs as $key => $value) {
            $fancyName = $names[$i];
            $len = mb_strlen($value, "UTF-8");
            switch ($types[$i]) {
                case "email":
                    if ($len > 50) {
                        $errors[] = "Email can't have more than 50 characters.";
                    } else if (preg_match("/^[\w.]+@[\w.]+\.[A-Za-z0-9]+$/", $value) != 1) {
                        $errors[] = "Email must be of form a@b.c where a and b may only contain letters, digits, periods, and underscores; and c may only contain letters and digits.";
                    }
                    break;
                case "name":
                    if ($len > 50) {
                        $errors[] = "$fancyName can't have more than 50 characters.";
                    } else if (preg_match("/^[A-Z][a-z]+$/", $value) != 1) {
                        $errors[] = "$fancyName must be an uppercase letter, followed by at least one lowercase letter.";
                    }
                    break;
                case "password":
                    if ($len > 64) {
                        $errors[] = "Passwords longer than 64 characters may allow a smaller password to also work.";
                    } elseif ($len < 8) {
                        $errors[] = "Password must contain at least 8 characters.";
                    } elseif (preg_match("/^[!-~]{8,64}$/", $value) != 1) {
                        $errors[] = "Password must contain printable ASCII characters, excluding spaces.";
                    }
                    break;
                case "sortColumn":
                    if ($value != "id" && $value != "email" && $value != "name" && $value != "username") {
                        $errors[] = "Invalid column to sort.";
                    }
                    break;
                case "sortDir":
                    if ($value != "asc" && $value != "desc") {
                        $errors[] = "Invalid sort direction.";
                    }
                    break;
                case "query":
                    if (str_starts_with($value, "e:")) {
                        if ($len > 52) {
                            $errors[] = "Email query is too long.";
                        } elseif (preg_match("/^(?:[\w.]+|[\w.]+@|[\w.]*@[\w.]+(?:\.[A-Za-z0-9]+)?)$/", substr($value, 2)) != 1) {
                            $errors[] = "Email query has improper form.";
                        }
                    } elseif (str_starts_with($value, "n:")) {
                        if ($len > 103) {
                            $errors[] = "Name query is too long.";
                        } elseif (preg_match("/^[A-Z]?[a-z]*(?: [A-Z][a-z]*)?$/", substr($value, 2)) != 1) {
                            $errors[] = "Name query has improper form.";
                        }
                    } elseif (str_starts_with($value, "u:")) {
                        if ($len > 32) {
                            $errors[] = "Username query is too long.";
                        } elseif (preg_match("/^[\w\-]{1,30}$/", substr($value, 2)) != 1) {
                            $errors[] = "Username query has improper form.";                            
                        }
                    } elseif ($value != "") {
                        $errors[] = "Invalid query header, only e:<email>, n:<name>, and u:<username> are valid.";
                    }
                    break;
                case "username":
                    if ($len > 30) {
                        $errors[] = "Username can't have more than 30 characters";
                    } elseif ($len < 5) {
                        $errors[] = "Username must have at least 5 characters";
                    } elseif (preg_match("/^[\w\-]{5,30}$/", $value) != 1) {
                        $errors[] = "Username may only contain letters, digits, underscores, and hyphens";
                    }
                    break;
                default:
                    throw new Exception("Unknown case " . $types[$i]);
            }
            $i++;
        }
        return $errors;
    }

    function get_role($conn, $username, $password) {
        $query = "SELECT id, password, role FROM user_credentials WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $username);
        if (!$stmt->execute()) {
            return [-2, "none"];
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            password_verify("hi", "$2y\$13\$f1234567890123456789012345678901234567890123456789012");
            return [-1, "none"];
        }
        if (password_verify($password, $row["password"])) {
            return [$row["id"], $row["role"]];
        }
        return [-1, "none"];
    }

?>