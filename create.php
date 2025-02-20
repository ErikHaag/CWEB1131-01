<?php
include "config/dbconfig.php";
$regexes = [
    "u_full_name" => "/^[A-Za-z ]{1,20}$/",
    "u_email" => "/^[\w.-]+@[\w.-]+\.[A-Za-z]{2,6}/",
    "u_phone" => "/^\d{10}$/",
    "u_role" => "/^(Admin|Librarian|Student|Teacher)$/",
    "i_name" => "/^[A-Za-z ]{1,30}$/",
    "i_category" => "/^[A-Za-z ]{1,30}$/",
    "i_available_status" => "/^(Available|Borrowed)$/",
    "b_date_time" => "/^\d{4}-\d{2}-\d{2}T[01]\d:[0-5]\d$/",
    "b_usage_location" => "/^(Classroom|Home|Lab|Library|Office)$/",
    "b_status" => "/^(Borrowed|Returned|Overdue)$/"
];

function sanitize(&$strings) {
    // use pass by reference to modify the given array
    $errors = [];
    foreach ($strings as $key => &$value) {
        $value = str_replace(";", "", $value);
        $value = htmlspecialchars($value);
        $value = stripslashes($value);
        $value = trim($value);
        if (strlen($value) == 0) {
            $errors[$key] = "Was reduced to spaces!";
        }
    }
    unset($value);
    return $errors;
}

$users = getAllData($conn, "SELECT user_id, full_name FROM users");
$items = getAllData($conn, "SELECT item_id, item_name FROM items");

$userIDs = [];
foreach ($users as $user) {
    $userIDs[] = $user["user_id"];
}

$itemIDs = [];
foreach ($items as $item) {
    $itemIDs[] = $item["item_id"];
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    //create inputs
    $inputs = ["user" => $_POST["user"] ?? "", "item" => $_POST["item"] ?? "", "dateBorrowed" => $_POST["dateBorrowed"] ?? "", "dateDue" => $_POST["dateDue"] ?? "", "usageLoc" => $_POST["usageLoc"] ?? "", "status" => $_POST["status"] ?? ""];
    //Sanitize
    $errors = sanitize($inputs);
    if (count($errors) == 0) {
        //Check
        if (!in_array($inputs["user"], $userIDs)) {
            $errors["user"] = "User does not exist.";
        }
        if (!in_array($inputs["item"], $itemIDs)) {
            $errors["item"] = "Item does not exist.";
        }
        if (preg_match($regexes["b_date_time"], $inputs["dateBorrowed"]) != 1) {
            $errors["dateBorrowed"] = "Invalid datetime.";
        }
        if (preg_match($regexes["b_date_time"], $inputs["dateDue"]) != 1) {
            $errors["dateDue"] = "Invalid datetime.";
        }
        if (preg_match($regexes["b_usage_location"], $inputs["usageLoc"]) != 1) {
            $errors["usageLoc"] = "Invalid location.";
        }
        if (preg_match($regexes["b_status"], $inputs["status"]) != 1) {
            $errors["status"] = "Invalid Status";
        }
    }
    if (count($errors) == 0) {
        //fix datetime
        $inputs["dateBorrowed"] = str_replace("T", " ", $inputs["dateBorrowed"]).":00";
        $inputs["dateDue"] = str_replace("T", " ", $inputs["dateDue"]).":00";
        // Execute
        try {
            // create query
            $query = "INSERT INTO `borrowings` (`user_id`, `item_id`, `borrowed_date`, `due_date`, `usage_location`, `status`) VALUES ( ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            // bind parameters
            $stmt->bindParam(1, $inputs["user"]);
            $stmt->bindParam(2, $inputs["item"]);
            $stmt->bindParam(3, $inputs["dateBorrowed"]);
            $stmt->bindParam(4, $inputs["dateDue"]);
            $stmt->bindParam(5, $inputs["usageLoc"]);
            $stmt->bindParam(6, $inputs["status"]);
            // execute it
            if ($stmt->execute()) {
                // send them back to index
                header("Location: index.html");
            }  else {
                $errors["generic"] = "Couldn't insert data.";
            }
        } catch (PDOException $e) {
            $errors["generic"] = $e->getMessage();
        }
    }
}




?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Page Title</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <!-- <script src='main.js'></script> -->
</head>

<body>
    <div class="center">
    <fieldset>
        <legend>Create new borrow...</legend>
        <form method="post">
            <p><?php echo $errors["generic"] ?? ""?></p>
            <div class="threeColGrid">
                <label for="user">User</label>
                <select id="user" name="user">
                    <option value="" selected>Please select an option...</option>
                    <?php
                    foreach ($users as $user) {
                        $v = $user["user_id"];
                        $text = $user["full_name"];
                        echo "<option value=\"{$v}\">{$text}</option>";
                    }
                    ?>
                </select>
                <p><?php echo $errors["user"] ?? "";?></p>
                <label for="item">Item Borrowed</label>
                <select id="item" name="item">
                    <option value="" selected>Please select an option...</option>
                    <?php
                    foreach ($items as $item) {
                        $v = $item["item_id"];
                        $text = $item["item_name"];
                        echo "<option value=\"{$v}\">{$text}</option>";
                    }
                    ?>
                </select>
                <p><?php echo $errors["item"] ?? "";?></p>
                <label for="dateBorrowed">Date borrowed</label>
                <input id="dateBorrowed" name="dateBorrowed" type="datetime-local">
                <p><?php echo $errors["dateBorrowed"] ?? "";?></p>
                <label for="dateDue">Date due</label>
                <input id="dateDue" name="dateDue" type="datetime-local">
                <p><?php echo $errors["dateDue"] ?? "";?></p>
                <label for="usageLoc">Usage location</label>
                <select id="usageLoc" name="usageLoc">
                    <option value="" selected>Please select an option...</option>
                    <option value="Classroom">Classroom</option>
                    <option value="Home">Home</option>
                    <option value="Library">Library</option>
                    <option value="Lab">Lab</option>
                    <option value="Office">Office</option>
                </select>
                <p><?php echo $errors["usageLoc"] ?? "";?></p>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="" selected>Please select an option...</option>
                    <option value="Borrowed">Borrowed</option>
                    <option value="Returned">Returned</option>
                    <option value="Overdue">Overdue</option>
                </select>
                <p><?php echo $errors["status"] ?? "";?></p>
            </div>
            <a href="index.html">Go back</a>
            <input type="submit">
        </form>
    </fieldset>
    </div>

</body>

</html>