<?php
require "config/dbconfig.php";
session_start();
$role = get_role($_SESSION["user_id"] ?? "", $_SESSION["password"] ?? "");
if ($role != "admin" && $role != "super_admin") {
    header("Location: index.php");
    exit();
}
$errors = [];
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $inputs = filter_assoc($_GET, ["id"]);
    $errors = sanitize($inputs);
    if (!empty($errors) || preg_match($regexes["pos_int"], $inputs["id"]) != 1) {
        echo "Invalid Id.";
        exit();
    }
    $inputs["id"] = (int) $inputs["id"];
    try {
        $query = "SELECT STORE_ID, FIRST_NAME, LAST_NAME, EMAIL, ADDRESS FROM customers WHERE CUSTOMER_ID = ? LIMIT 0,1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $inputs["id"], PDO::PARAM_INT);
        if ($stmt->execute()) {
            if ($stmt->rowCount() == 1) {
                $fill = $stmt->fetch(PDO::FETCH_ASSOC);
                $fill["CUSTOMER_ID"] = $inputs["id"];
            } else {
                echo "That ID doesn't exist in our database!";
                return;
            }
        } else {
            echo "An error occurred when processing your request.";
            return;
        }
    } catch (Exception $e) {
        echo "An error occurred when processing your request.\n" . $e->getMessage();
        return;
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $query = "SELECT DISTINCT STORE_ID from customers";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $validIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $inputs = filter_assoc($_POST, ["CUSTOMER_ID", "STORE_ID", "FIRST_NAME", "LAST_NAME", "EMAIL", "ADDRESS"]);
        $errors = sanitize($inputs);
    } catch (Exception $e) {
        $errors["generic"] = "An error occurred when processing your request.\n" . $e->getMessage();
    }

    $fill = $inputs;
    if (count($errors) == 0) {
        // so far so good
        if (preg_match($regexes["pos_int"], $inputs["CUSTOMER_ID"]) != 1) {
            $errors["generic"] = "You changed the hidden input, didn't you?";
            $fill["CUSTOMER_ID"] = $_GET["id"];
        } else {
            $inputs["CUSTOMER_ID"] = (int) $inputs["CUSTOMER_ID"];
        }
        if (preg_match($regexes["pos_int"], $inputs["STORE_ID"]) != 1) {
            $errors["STORE_ID"] = "That's not a valid id.";
        } else {
            // cast the id to an integer
            $inputs["STORE_ID"] = (int) $inputs["STORE_ID"];
            if (!in_array($inputs["STORE_ID"], $validIds)) {
                $errors["STORE_ID"] = "That id doesn't exist in our database.";
            }
        }
        if (strlen($inputs["FIRST_NAME"]) > 50 || preg_match($regexes["name"], $inputs["FIRST_NAME"]) != 1) {
            $errors["FIRST_NAME"] = "Invalid first name.";
        }
        if (strlen($inputs["LAST_NAME"]) > 50 || preg_match($regexes["name"], $inputs["LAST_NAME"]) != 1) {
            $errors["LAST_NAME"] = "Invalid last name.";
        }
        if (strlen($inputs["EMAIL"]) > 100 || preg_match($regexes["email"], $inputs["EMAIL"]) != 1) {
            $errors["EMAIL"] = "Invalid email.";
        }
        if (preg_match($regexes["address"], $inputs["ADDRESS"]) != 1) {
            $errors["ADDRESS"] = "Invalid address.";
        }
    }
    if (count($errors) == 0) {
        //We're safe for editing!
        $query = "UPDATE customers SET STORE_ID = ?, FIRST_NAME = ?, LAST_NAME = ?, EMAIL = ?, ADDRESS = ? WHERE CUSTOMER_ID = ? LIMIT 1";
        try {
            $stmt = $conn->prepare($query);
            $stmt->bindValue(1, $inputs["STORE_ID"], PDO::PARAM_INT);
            $stmt->bindValue(2, $inputs["FIRST_NAME"]);
            $stmt->bindValue(3, $inputs["LAST_NAME"]);
            $stmt->bindValue(4, $inputs["EMAIL"]);
            $stmt->bindValue(5, $inputs["ADDRESS"]);
            $stmt->bindValue(6, $inputs["CUSTOMER_ID"], PDO::PARAM_INT);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors["generic"] = "An error occurred when processing your request.";
            }
        } catch (Exception $e) {
            $errors["generic"] = "An error occurred when processing your requeest.\n" . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Edit customer</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='dashboard.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='form.css'>
</head>

<body>
    <div class="column">
        <fieldset>
            <legend>Editing customer...</legend>
            <form method="post">
                <p class="error"><?php echo $errors["generic"] ?? ""; ?></p>
                <input type="hidden" name="CUSTOMER_ID" value="<?php echo $fill["CUSTOMER_ID"] ?? ""; ?>">
                <div class="threecol">
                    <label for="STORE_ID">Store ID: </label>
                    <input type="number" min="1" id="STORE_ID" name="STORE_ID"
                        value="<?php echo $fill["STORE_ID"] ?? ""; ?>">
                    <p class="error"><?php echo $errors["STORE_ID"] ?? ""; ?></p>
                    <label for="FIRST_NAME">First name: </label>
                    <input type="text" id="FIRST_NAME" name="FIRST_NAME"
                        value="<?php echo $fill["FIRST_NAME"] ?? ""; ?>">
                    <p class="error"><?php echo $errors["FIRST_NAME"] ?? ""; ?></p>
                    <label for="LAST_NAME">Last name: </label>
                    <input type="text" id="LAST_NAME" name="LAST_NAME" value="<?php echo $fill["LAST_NAME"] ?? ""; ?>">
                    <p class="error"><?php echo $errors["LAST_NAME"] ?? ""; ?></p>
                    <label for="EMAIL">Email: </label>
                    <input type="text" id="EMAIL" name="EMAIL" value="<?php echo $fill["EMAIL"] ?? ""; ?>">
                    <p class="error"><?php echo $errors["EMAIL"] ?? ""; ?></p>
                    <label for="ADDRESS">Address: </label>
                    <input type="text" id="ADDRESS" name="ADDRESS" value="<?php echo $fill["ADDRESS"] ?? ""; ?>">
                    <p class="error"><?php echo $errors["ADDRESS"] ?? ""; ?></p>
                </div>
                <div class="mt-5">
                    <a href="dashboard.php" id="back" class="btn">Go back</a>
                    <input id="submit" class="btn" type="submit">
                </div>
                <p class="messages"><?php echo $success ? "Customer has been changed." : ""; ?></p>
            </form>
        </fieldset>
    </div>
</body>

</html>