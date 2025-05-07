<?php
    // database connection
    require "config/dbConfig.php";
    // utility functions
    require "utils.php";

    $genericErrors = [];
    $loginErrors = [];
    $hasLoginErrors = false;
    $registerErrors = [];
    $hasRegisterErrors = false;
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if ($_POST["formType"] == "login") {

        } elseif ($_POST["formType"] == "register") {
            $input = [];
            foreach (["firstName", "lastName", "username", "password", "email"] as $k) {
                $input[$k] = $_POST[$k];
            }
            scrub($input);

            $registerErrors = validate($input, ["name", "name", "username", "password", "email"], ["First name", "Last name", "", "", ""]);

            print_r($registerErrors);   

            if (isset($_FILES["icon"])) {
                // Handle image
                // print_r($_FILES);

                // mime_content_type($_FILES["icon"]["tmp_name"]);
                // return;
            }

            if (empty($registerErrors)) {
                // todo: database
            } else {
                $hasRegisterErrors = true;
            }
        } else {

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
</head>
<body>
    <div class="container mt-5">
        <ul class="nav justify-content-center">
            <li class="nav-item">
                <button class="btn btn-sm btn-primary disabled" style="width:10em" id="loginNav">Log in</button>
            </li>
            <li class="nav-item">
                <button class="btn btn-sm btn-secondary" style="width:10em" id="registerNav">Register</button>
            </li>
        </ul>
        <div class="border border-3 rounded p-2">
            <div id="loginContainer">
                <form method="post">
                    <input type="hidden" name="formtype" value="register">
                </form>
            </div>
            <div id="registerContainer" hidden>
                <form enctype="multipart/form-data" method="post">
                    <?php
                        if ($hasRegisterErrors) {
                            echo "<div class=\"alert alert-danger\">";
                            echo "Oops...";
                            echo "<ul>";
                            foreach ($registerErrors as $error) {
                                echo "<li>";
                                echo $error;
                                echo "</li>";
                            }
                            echo "</ul>";
                            echo "</div>";
                        }
                    ?>
                    <input type="hidden" name="formType" value="register">
                    <div class="columns-3">
                        <div class="m-2">
                            <label for="firstNameR" class="form-label">First name</label>
                            <input name="firstName" id="firstNameR" class="form-control" value="<?= $hasRegisterErrors ? $input["firstName"] : "" ?>" autocomplete="given-name" required>
                        </div>
                        <div class="m-2">
                            <label for="lastNameR" class="form-label">Last name</label>
                            <input name="lastName" id="lastNameR" class="form-control" value="<?= $hasRegisterErrors ? $input["lastName"] : "" ?>" autocomplete="family-name" required>
                        </div>
                        <div class="m-2">
                            <label for="usernameR" class="form-label">User name</label>
                            <input name="username" id="usernameR" class="form-control" value="<?= $hasRegisterErrors ? $input["username"] : "" ?>" autocomplete="username" required>
                        </div>
                        <div class="m-2">
                            <label for="passwordR" class="form-label">Password</label>
                            <input type="password" id="passwordR" name="password" class="form-control"   autocomplete="new-password" required>
                        </div>
                        <div class="m-2">
                            <label for="emailR" class="form-label">Email</label>
                            <input name="email" id="emailR" class="form-control" value="<?= $hasRegisterErrors ? $input["email"] : "" ?>" autocomplete="email" required>
                        </div>
                        <div class="m-2">
                            <label for="iconR" class="form-label">Profile icon</label>
                            <input type="file" id="iconR" name="icon" class="form-control">
                        </div>
                    </div>
                    <div class="d-flex flex-row-reverse">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
        </div>
    </div>
</body>
<script>
    let loginOpen = <?=$_POST["formType"] == "register" ? "false" : "true"; ?>;
</script>
<script src='loginAndRegister.js'></script>
</html>