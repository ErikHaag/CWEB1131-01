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
    "b_id" => "/^[1-9]\d*$/",
    "b_date" => "/^\d{4}-\d{2}-\d{2}$/",
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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    //create inputs
    $inputs = [$_POST["user"] ?? "", $_POST["itemBorrowed"] ?? "", $_POST["dateBorrowed"] ?? "", $_POST["dateDue"] ?? "", $_POST["usageLoc"] ?? "", $_POST["status"] ?? ""];
    // print_r($inputs);
    //Sanitize
    $errors = sanitize($inputs);
    if (count($errors) == 0) {
        //Check

        //Execute
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
                <label for="itemBorrowed">Item Borrowed</label>
                <select id="itemBorrowed" name="itemBorrowed">
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
                <input id="dateBorrowed" name="dateBorrowed" type="date">
                <p></p>
                <label for="dateDue">Date due</label>
                <input id="dateDue" name="dateDue" type="date">
                <p></p>
                <label for="usageLoc">Usage location</label>
                <select id="usageLoc" name="usageLoc">
                    <option value="" selected>Please select an option...</option>
                    <option value="Classroom">Classroom</option>
                    <option value="Home">Home</option>
                    <option value="Library">Library</option>
                    <option value="Lab">Lab</option>
                    <option value="Office">Office</option>
                </select>
                <p></p>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="" selected>Please select an option...</option>
                    <option value="Borrowed">Borrowed</option>
                    <option value="Returned">Returned</option>
                    <option value="Overdue">Overdue</option>
                </select>
                <p></p>
            </div>
            <a href="index.php">Go back</a>
            <input type="submit">
        </form>
    </fieldset>
    </div>

</body>

</html>