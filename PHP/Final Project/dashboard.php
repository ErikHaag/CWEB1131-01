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
    <?php
    if ($role == "superAdmin") {
        ?>
        <div class="modal" id="deleteModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Delete user</h4>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text">Are you sure you want to delete <strong>nill</strong>?</p>
                        <p class="text text-bg-info"></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-danger" id="confirmDelete" disabled>Yes</button>
                    </div>
                </div>
            </div>
        </div><?php
    }
    ?>
    <div class="container mt-5 mx-5">
        <div class="alert alert-warning" id="alerts" hidden>
            <ul></ul>
            <ul></ul>
        </div>
        <!-- Hey! It's me! -->
        <h2>Hello, <?= $_SESSION["username"] ?></h2>
        <div class="container my-5">
            <h5>Info</h5>
            <table>
                <tbody id="selfTable" class="table table-bordered">
                </tbody>
            </table>
            <a class="btn btn-secondary" href="edit.php?username=<?= $_SESSION["username"] ?>">Edit</a>
            <a class="btn btn-info ms-2" href="logout.php">Log out</a>
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
            <tfoot>
                <tr>
                    <td>
                        <div class="d-flex justify-content-center align-items-center">
                            <p class="text me-2 my-0">Page</p>
                            <button class="button" id="prevPage">&lt;</button>
                            <p class="text mx-1 my-0" id="pageNumber">1/1</p>
                            <button class="button" id="nextPage">&gt;</button>
                        </div>
                        <div class="d-flex justify-content-center align-items-center">
                            <p class="text me-2 my-0">Rows per page:</p>
                            <select id="rowCount">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                    </td>
                    <td colspan="2">
                        <div class="d-flex justify-content-center align-items-center expand-x">
                            <p class="text me-2 my-0"></p>
                            <input id="query" placeholder="n:John">
                            <p class="text ms-4 me-2 my-0">Sort by</p>
                            <select id="sortCol">
                                <option value="id" selected>id</option>
                                <option value="email">email</option>
                                <option value="name">name</option>
                                <option value="username">username</option>
                            </select>
                            <p class="text mx-2 my-0">in</p>
                            <select id="sortDir">
                                <option value="asc" selected>acending order</option>
                                <option value="desc">decending order</option>
                            </select>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

<script>
    const username = "<?= $_SESSION["username"] ?>";
    const password = "<?= $_SESSION["password"] ?>";
    <?php
    if ($role == "superAdmin") {
        ?>const deleteModal = new bootstrap.Modal(document.getElementById("deleteModal"));
        let usernameToDelete = ""; <?php
    }
    ?>


    function additionalButtons(username) {
        <?php
        // PHP and JS mingling <3
        if ($role == "superAdmin") {
            echo "\n\t";
            ?>return `<a class="btn btn-warning" href="edit.php?username=${username}">Edit</a><button class="btn btn-danger ms-2" id="deleteU_${username}">Delete</button>`; <?php
        } elseif ($role == "admin") {
            ?>return `<a class="btn btn-warning" href="edit.php?username=${username}">Edit</a>`; <?php
        } else {
            // They'll never know...
            ?>/* TODO */
            return ""; <?php
        }
        echo "\n\t";
        ?>}

    async function handleAdditionalButtons(id) {
        <?php
        if ($role == "superAdmin") {
            ?>let dMBody = deleteModal._element.children[0].children[0].children[1];
            if (id.startsWith("deleteU_")) {
                usernameToDelete = id.substring(8);
                // set the strong element, in style (tm)
                dMBody.children[0].children[0].innerText = usernameToDelete;
                dMBody.children[1].innerText = "";
                document.getElementById("confirmDelete").removeAttribute("disabled");
                deleteModal.show();
            } else if (id == "confirmDelete") {
                document.getElementById("confirmDelete").setAttribute("disabled", "");
                let response = await fetch("./api.php", {
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ username: username, password: password, userToDelete: usernameToDelete }),
                    method: "DELETE"
                });
                let data = await response.json();
                console.log(data);
                if (data.type == "success") {
                    dMBody.children[1].innerText = "Successfully deleted user.";
                    await updateTable();
                    updatePageDisp();
                } else if (data.type == "error") {
                    dMBody.children[1].innerText = "Oops...\n" + data.message;
                    document.getElementById("confirmDelete").removeAttribute("disabled");

                }
            } <?php
        } else {
            ?>/* TODO */<?php
        }
        echo "\n\t";
        ?>}
</script>
<script src='dashboard.js'></script>

</html>