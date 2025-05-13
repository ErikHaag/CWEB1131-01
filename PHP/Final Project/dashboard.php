<?php
session_start();
require "config/dbConfig.php";
require "utils.php";

[$id, $role] = get_role($conn, $_SESSION["username"], $_SESSION["password"]);

if ($role == "none") {
    if ($id == -1) {
        header("Location: logout.php");
    } else if ($id == -2) {
        // service unavailable
        http_response_code(503);
    }
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Page Title</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel='stylesheet' type='text/css' media='screen' href='dashboard.css'>
</head>

<body>
    <div class="container mt-5 mx-5">
        <div class="alert alert-warning" id="alerts" hidden>
            <ul></ul>
            <ul></ul>
        </div>
        <h2>Hello, <?= $_SESSION["username"] ?></h2>
        <div class="container my-5">
            <h5>Info</h5>
            <table>
                <tbody id="selfTable" class="table table-bordered">
                </tbody>
            </table>
            <button id="editSelf">Edit</button>
        </div>
        <hr>
        <table id="usersTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Username</th>
                    <th>Info</th>
                </tr>
            </thead>
            <tbody id="table">
                <tr>
                    <td id="loadingTd" colspan="3">Hang on,.......</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

<script>
    const username = "<?= $_SESSION["username"] ?>";
    const password = "<?= $_SESSION["password"] ?>";
</script>

<script src='dashboard.js'></script>

</html>