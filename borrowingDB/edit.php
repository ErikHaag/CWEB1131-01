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
    "b_date_time" => "/^\d{4}-\d{2}-\d{2}T[01]\d:[0-5]\d$/",
    "b_usage_location" => "/^(Classroom|Home|Lab|Library|Office)$/",
    "b_status" => "/^(Borrowed|Returned|Overdue)$/"
];

function sanitize(&$strings)
{
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

$borrows = getAllData($conn, "SELECT borrow_id FROM borrowings");
$users = getAllData($conn, "SELECT user_id, full_name FROM users");
$items = getAllData($conn, "SELECT item_id, item_name FROM items");

$borrowIDs = [];
foreach ($borrows as $borrow) {
    $borrowIDs[] = $borrow["borrow_id"];
}

$userIDs = [];
foreach ($users as $user) {
    $userIDs[] = $user["user_id"];
}

$itemIDs = [];
foreach ($items as $item) {
    $itemIDs[] = $item["item_id"];
}

$errors = [];
$fill = [];
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    //create inputs
    $inputs = ["id" => $_POST["borrowId"], "user" => $_POST["user"] ?? "", "item" => $_POST["item"] ?? "", "dateBorrowed" => $_POST["dateBorrowed"] ?? "", "dateDue" => $_POST["dateDue"] ?? "", "usageLoc" => $_POST["usageLoc"] ?? "", "status" => $_POST["status"] ?? ""];
    //sanitize
    $errors = sanitize($inputs);
    if (count($errors) == 0) {
        //Check
        if (!in_array($inputs["id"], $borrowIDs)) {
            $errors["generic"] = "Why did you touch that?";
        }
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
        $inputs["dateBorrowed"] = str_replace("T", " ", $inputs["dateBorrowed"]) . ":00";
        $inputs["dateDue"] = str_replace("T", " ", $inputs["dateDue"]) . ":00";
        // Execute
        try {
            // create query
            $query = "UPDATE `borrowings` SET user_id = ?, item_id = ?, borrowed_date = ?, due_date = ?, usage_location = ?, status = ? WHERE borrow_id = ?";
            $stmt = $conn->prepare($query);
            // bind parameters
            $stmt->bindValue(1, $inputs["user"], PDO::PARAM_INT);
            $stmt->bindValue(2, $inputs["item"], PDO::PARAM_INT);
            $stmt->bindValue(3, $inputs["dateBorrowed"]);
            $stmt->bindValue(4, $inputs["dateDue"]);
            $stmt->bindValue(5, $inputs["usageLoc"]);
            $stmt->bindValue(6, $inputs["status"]);
            $stmt->bindValue(7, $inputs["id"], PDO::PARAM_INT);
            // execute it
            if ($stmt->execute()) {
                // send them back to index
                header("Location: index.html?message=2");
            } else {
                $errors["generic"] = "Couldn't change data.";
            }
        } catch (PDOException $e) {
            $errors["generic"] = $e->getMessage();
        }
    }
}
$inputs = ["id" => $_GET["id"] ?? ""];
$GETErrors = sanitize($inputs);
if (!empty($GETErrors) || preg_match($regexes["b_id"], $inputs["id"]) != 1) {
    echo "This page is not for you.";
    return;
}
$query = "SELECT user_id, item_id, borrowed_date, due_date, usage_location, status FROM borrowings WHERE borrow_id = ? LIMIT 0,1";
$stmt = $conn->prepare($query);
$stmt->bindValue(1, $inputs["id"], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row == false) {
    echo "ID " . $inputs["id"] . " does not exist!";
    return;
}
$fill["borrow_id"] = $inputs["id"];
$fill["user_id"] = $row["user_id"];
$fill["item_id"] = $row["item_id"];
$fill["borrowed_date"] = substr_replace(substr_replace($row["borrowed_date"], "T", 10, 1), "", 16, 3);
$fill["due_date"] = substr_replace(substr_replace($row["due_date"], "T", 10, 1), "", 16, 3);
$fill["usage_location"] = $row["usage_location"];
$fill["status"] = $row["status"];
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
            <legend>Editing borrow...</legend>
            <form method="post">
                <p><?php echo $errors["generic"] ?? "" ?></p>
                <input name="borrowId" type="hidden" value="<?php echo $fill["borrow_id"]; ?>">
                <div class="threeColGrid">
                    <label for="user">User</label>
                    <select id="user" name="user">
                        <option value="">Please select an
                            option...</option>
                        <?php
                        foreach ($users as $user) {
                            $v = $user["user_id"];
                            $text = $user["full_name"];
                            echo "<option value=\"{$v}\"";
                            echo $fill["user_id"] == $v ? " selected" : "";
                            echo ">{$text}</option>";
                        }
                        ?>
                    </select>
                    <p><?php echo $errors["user"] ?? ""; ?></p>
                    <label for="item">Item Borrowed</label>
                    <select id="item" name="item">
                        <option value="" selected>Please select an option...</option>
                        <?php
                        foreach ($items as $item) {
                            $v = $item["item_id"];
                            $text = $item["item_name"];
                            echo "<option value=\"{$v}\"";
                            echo $fill["item_id"] == $v ? " selected" : "";
                            echo ">{$text}</option>";
                        }
                        ?>
                    </select>
                    <p><?php echo $errors["item"] ?? ""; ?></p>
                    <label for="dateBorrowed">Date borrowed</label>
                    <input id="dateBorrowed" name="dateBorrowed" type="datetime-local"
                        value="<?php echo $fill["borrowed_date"]; ?>">
                    <p><?php echo $errors["dateBorrowed"] ?? ""; ?></p>
                    <label for="dateDue">Date due</label>
                    <input id="dateDue" name="dateDue" type="datetime-local" value="<?php echo $fill["due_date"]; ?>">
                    <p><?php echo $errors["dateDue"] ?? ""; ?></p>
                    <label for="usageLoc">Usage location</label>
                    <select id="usageLoc" name="usageLoc">
                        <option value="" selected>Please select an option...</option>
                        <?php
                        foreach (["Classroom", "Home", "Library", "Lab", "Office"] as $loc) {
                            echo "<option value=\"$loc\"";
                            echo $fill["usage_location"] == $loc ? " selected" : "";
                            echo ">$loc</option>";
                        }
                        ?>
                    </select>
                    <p><?php echo $errors["usageLoc"] ?? ""; ?></p>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Please select an option...</option>
                        <?php
                        foreach (["Borrowed", "Returned", "Overdue"] as $status) {
                            echo "<option value=\"$status\"";
                            echo $fill["status"] == $status ? " selected" : "";
                            echo ">$status</option>";
                        }
                        ?>
                    </select>
                    <p><?php echo $errors["status"] ?? ""; ?></p>
                </div>
                <a href="index.html">Go back</a>
                <input type="submit">
            </form>
        </fieldset>
    </div>

</body>

</html>