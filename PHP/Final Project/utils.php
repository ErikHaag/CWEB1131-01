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


?>