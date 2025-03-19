<?php
require "config/dbconfig.php";
session_start();
$role = get_role($_SESSION["user_id"] ?? "", $_SESSION["password"] ?? "");
if ($role == "") {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Awesome database #2</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='dashboard.css'>
</head>

<body>
    <div class="column">
        <h1>Dashboard</h1>
        <p>Hello, <?php
        switch ($role) {
            case "admin":
                echo "admin";
                break;
            case "super_admin":
                echo "administrator";
                break;
            default:
                echo "user";
                break;
        }
        echo " " . $_SESSION["user_name"];
        ?></p>
        <table>
            <thead>
                <th>Customer ID</th>
                <th>Store ID</th>
                <th class="sortable" id="name">Name</th>
                <th class="sortable" id="email">Email</th>
                <th class="sortable" id="address">Address</th>
                <?php
                if ($role == "admin" || $role == "super_admin") {
                    echo "<th>Action</th>";
                }
                ?>
            </thead>
            <tbody id="table">
                <td colspan="6">Hang on...</td>
            </tbody>
        </table>
        <div class="row">
            <div class="row mr-5">
                <button id="previous" class="btn">&lt;</button>
                <div id="pageNumber">1</div>
                <button id="next" class="btn">&gt;</button>
                <label for="search">Search</label>
                <input id="search" placeholder="n:John">
                <label for="rows">Rows per page: </label>
                <select id="rows">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
            <div class="ml-5">
                <a href="./create.php" id="create" class="btn">Create</a>
            </div>
        </div>
        <div class="mt-2">
            <a href="log_out.php" id="logout" class="btn">Log out</a>
        </div>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const userID = <?php echo $_SESSION["user_id"]; ?>;
    const password = "<?php echo $_SESSION["password"]; ?>";

    function createRow(row) {
        let rowHTML = "<tr>";
        for (const col of ["customerID", "storeID", "name", "email", "address"]) {
            rowHTML += "<td>";
            rowHTML += row[col];
            rowHTML += "</td>";
        } <?php
        if ($role == "admin" || $role == "super_admin") {
            echo "\n\t\trowHTML += \"<td>\";\n";
            echo "\t\trowHTML += \"<a href=\\\"edit.php?id=\" + row.customerID + \"\\\" class=\\\"btn edit\\\">Edit</a>\";\n";
            if ($role == "super_admin") {
                echo "\t\trowHTML += \"<button id=\\\"delete\" + row.customerID + \"\\\" class=\\\"btn delete\\\">Delete</button>\";\n";
            }
            echo "\t\trowHTML += \"</td>\";\n";
        }
        ?>        rowHTML += "</tr>";
        return rowHTML;
    }
    <?php
    if ($role == "super_admin") {
        // remove this function if you aren't permitted to use it.
        echo "async function deleteCustomer(id) {\n";
        echo "\t\tconst choice = await swal.fire({\n";
        echo "\t\t\ttitle: \"Are you sure?\",\n";
        echo "\t\t\ttext: \"You won't be able to recover this customer!\",\n";
        echo "\t\t\ticon: \"warning\",\n";
        echo "\t\t\tshowCancelButton: true,\n";
        echo "\t\t\tcancelButtonColor: \"#3085d6\",\n";
        echo "\t\t\tconfirmButtonColor: \"#d33\",\n";
        echo "\t\t\tconfirmButtonText: \"Yes, delete it\"\n";
        echo "\t\t});\n";
        echo "\t\tif (!choice.isConfirmed) {\n";
        echo "\t\t\treturn;\n";
        echo "\t\t}\n";
        echo "\t\tconst response = await fetch(\"./API.php\", {\n";
        echo "\t\t\tmethod: \"POST\",\n";
        echo "\t\t\theaders: {\n";
        echo "\t\t\t\t\"Content-Type\": \"application/json\"\n";
        echo "\t\t\t},\n";
        echo "\t\t\tbody: JSON.stringify({\n";
        echo "\t\t\t\trequestType: \"deleteCustomer\",\n";
        echo "\t\t\t\tid: id,\n";
        echo "\t\t\t\tuserID: userID,\n";
        echo "\t\t\t\tpassword: password \n";
        echo "\t\t\t})\n";
        echo "\t\t});\n";
        echo "\t\tlet reply;\n";
        echo "\t\ttry {\n";
        echo "\t\t\treply = await response.json();\n";
        echo "\t\t} catch (e) {\n";
        echo "\t\t\tawait swal.fire({\n";
        echo "\t\t\t\ttitle: \"Oops\",\n";
        echo "\t\t\t\ttext: \"An error occurred.\",\n";
        echo "\t\t\t\ticon: \"error\"\n";
        echo "\t\t\t});\n";
        echo "\t\t\treturn;\n";
        echo "\t\t}\n";
        echo "\t\tif (reply.type == \"success\") {\n";
        echo "\t\t\tawait swal.fire({\n";
        echo "\t\t\t\ttitle: \"Success!\",\n";
        echo "\t\t\t\ttext: \"Successfully removed customer.\",\n";
        echo "\t\t\t\ticon: \"success\"\n";
        echo "\t\t\t});\n";
        echo "\t\t\tupdateTable();\n";
        echo "\t\t} else if (reply.type == \"error\") {\n";
        echo "\t\t\tawait swal.fire({\n";
        echo "\t\t\t\ttitle: \"Oops\",\n";
        echo "\t\t\t\ttext: \"An error occurred.\",\n";
        echo "\t\t\t\ticon: \"error\"\n";
        echo "\t\t\t});\n";
        echo "\t\t}\n";
        echo "\t}";
    }
    ?>
</script>
<script src='main.js'></script>

</html>