<?php
include "config/dbconfig.php";

$regexes = [
    "u_full_name" => "/^[A-Za-z ]{1,20}$/",
    "u_email"=> "/^[\w.-]+@[\w.-]+\.[A-Za-z]{2,6}/",
    "u_phone" => "/^\d{10}$/",
    "u_role" => "/^(Admin|Librarian|Student|Teacher)$/",
    "i_name" => "/^[A-Za-z ]{1,30}$/",
    "i_category" => "/^[A-Za-z ]{1,30}$/",
    "i_available_status" => "/^(Available|Borrowed)$/",
    "b_date" => "/^\d{4}-\d{2}-\d{2}-\d{2}$/",
    "b_usage_location" => "/^(Classroom|Home|Lab|Library|Office)$/",
    "b_status" => "/^(Borrowed|Returned|Overdue)$/"
];


if ($_SERVER['REQUEST_METHOD'] == "GET") {
    //Get the data
    $users = getAllData($conn, "SELECT user_id, full_name FROM users");
    $items = getAllData($conn, "SELECT item_id, item_name FROM items");
    $borrows = getAllData($conn, "SELECT user_id, item_id, borrowed_date, due_date, status FROM borrowings");
    
    //convert {i->{id->j, name->s}, ...} to {j->s, ...} 
    $usersArray = [];
    foreach ($users as $u) {
        $usersArray[$u["user_id"]] = $u["full_name"];
    }
    $itemsArray = [];
    foreach ($items as $i) {
        $itemsArray[$i["item_id"]] = $i["item_name"];
    }

    //turn it into JSON
    $list = [];
    foreach ($borrows as $b) {
        if (!empty($usersArray[$b["user_id"]]) && !empty($itemsArray[$b["item_id"]])) {
            $borrow = "{";
            $borrow .= "\"userName\": \"".$usersArray[$b["user_id"]]."\",";
            $borrow .= "\"itemName\": \"".$itemsArray[$b["item_id"]]."\",";
            $borrow .= "\"borrowedDate\": \"".$b["borrowed_date"]."\",";
            $borrow .= "\"dueDate\": \"".$b["due_date"]."\",";
            $borrow .= "\"status\": \"".$b["status"]."\"";
            $borrow .= "}";
            $list[] = $borrow;
        }
    }
    //echo the JSON for fetch()
    echo "[";
    echo implode(",", $list);
    echo "]";
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {

    function sanitize(&$strings) {
        // use pass by reference modify the given array
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

    // get the body of fetch()
    $requestData = json_decode(file_get_contents('php://input'), true);
    $inputs = [];
    switch ($requestData["requestType"] ?? "") {
        default:
          echo "{ \"type\": \"error\", \"message\": \"Invalid request\" }";
          break;
    }               
}
?>