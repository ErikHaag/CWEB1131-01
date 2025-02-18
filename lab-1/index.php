<?php
include "config/dbconfig.php";

$users = getData($conn, "SELECT user_id, full_name FROM users");
$items = getData($conn, "SELECT item_id, item_name FROM items");
$borrows = getData($conn, "SELECT user_id, item_id, borrowed_date, due_date, status FROM borrowings");

// convert {i->{id->j, name->s}, ...} to {j->s, ...} 
$usersArray = [];
foreach ($users as $u) {
    $usersArray[$u["user_id"]] = $u["full_name"];
}
$itemsArray = [];
foreach ($items as $i) {
    $itemsArray[$i["item_id"]] = $i["item_name"];
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Borrowing</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <!-- <script src='main.js'></script> -->
</head>

<body>
    <div class="center">
        <h1>Borrowing database</h1>
    </div>
    <div class="center">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Item</th>
                    <th>Date borrowed</th>
                    <th>Date due</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($borrows)) {
                    echo "<tr>";
                    echo "<td colspan=\"5\">";
                    echo "<div class=\"center\">";
                    echo "No data";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                } else {
                    foreach ($borrows as $b) {
                        if (!empty($usersArray[$b["user_id"]]) && !empty($itemsArray[$b["item_id"]])) {
                            // we have both foriegn keys
                            echo "<tr>";
                            echo "<td>" . $usersArray[$b["user_id"]] . "</td>";
                            echo "<td>" . $itemsArray[$b["item_id"]] . "</td>";
                            echo "<td>" . $b["borrowed_date"] . "</td>";
                            echo "<td>" . $b["due_date"] . "</td>";
                            echo "<td>" . $b["status"] . "</td>";
                            echo "</tr>";
                        }
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>