<?php
// database connection
require "config/dbConfig.php";
// utility functions
require "utils.php";

$genericMessages = [];
$loginErrors = [];
$registerErrors = [];
$hasLoginErrors = false;
$hasRegisterErrors = false;
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($_POST["formType"] == "login") {
        $input = [];
        foreach (["username", "password"] as $k) {
            $input[$k] = $_POST[$k];
        }
        scrub($input);

        $loginErrors = validate($input, ["username", "password"], ["", ""]);

        if (empty($loginErrors)) {
            $query = "SELECT id, password FROM user_credentials WHERE username = ? LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(1, $input["username"]);
            if (!$stmt->execute()) {
                $loginErrors[] = "An unexpected error occurred, please try again later.";
                goto fail;
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                // timing attacks? No thanks
                password_verify("hi", "$2y\$13\$f1234567890123456789012345678901234567890123456789012");
                $loginErrors[] = "Invalid username or password.";
                goto fail;
            }
            if (!password_verify($input["password"], $row["password"])) {
                $loginErrors[] = "Invalid username or password.";
                goto fail;
            }

            $row["id"] = (int) $row["id"];
            if (password_needs_rehash($input["password"], PASSWORD_DEFAULT, ["cost" => 13])) {
                // Rehash user's password if better methods are implemented
                $rehashed = password_hash($input["password"], PASSWORD_DEFAULT, ["cost" => 13]);
                try {
                    $query = "UPDATE user_credentials SET password = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bindValue(1, $rehashed);
                    $stmt->bindValue(2, $row["id"], PDO::PARAM_INT);
                } catch (Exception $e) {
                    // oh well...
                }
            }

            session_start();
            // not the most secure thing, I wish the documentation was a little clearer on how to deal with regenerate_session_id().
            $_SESSION["username"] = $input["username"];
            $_SESSION["password"] = $input["password"];
            header("Location: dashboard.php");
        }
    } elseif ($_POST["formType"] == "register") {
        $input = [];
        foreach (["firstName", "lastName", "username", "password", "email"] as $k) {
            $input[$k] = $_POST[$k];
        }
        scrub($input);

        $registerErrors = validate($input, ["name", "name", "username", "password", "email"], ["First name", "Last name", "", "", ""]);

        if (empty($registerErrors)) {
            $simplifiedUsername = strtolower($input["username"]);
            $query = "SELECT username FROM user_credentials";
            $stmt = $conn->prepare($query);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC, 0)) {
                if ($simplifiedUsername == strtolower($row["username"])) {
                    $registerErrors[] = "A user with that username already exists.";
                    break;
                }
            }
        }
        $image_name = "icons/standard_user.png";
        if (isset($_FILES["icon"])) {
            switch ($_FILES["icon"]["error"]) {
                case 0:
                    // Handle image
                    $tempName = $_FILES["icon"]["tmp_name"];
                    $contentType = mime_content_type($tempName);
                    if (is_uploaded_file($tempName)) {
                        $image_name = "icons/u_" . $input["username"];
                        switch ($contentType) {
                            case "image/png":
                                $image_name .= ".png";
                                break;
                            case "image/jpeg":
                                $image_name .= ".jpeg";
                                break;
                            case "image/gif":
                                $image_name .= ".gif";
                            default:
                                $registerErrors[] = "Invalid file type";
                                // Get out of here
                                break 2;
                        }
                        if (!move_uploaded_file($tempName, $image_name)) {
                            // ah come on.
                            $genericMessages[] = "A small problem occurred when uploading your file, contact the administrator to help with this problem.";
                            $image_name = "icons/standard_user.png";
                        }
                    }
                    break;
                case 1:
                case 2:
                    $registerErrors[] = "Your file bigger than 40 Mebibytes";
                    break;
                case 4:
                    break;
                default:
                    $registerErrors[] = "An unexpected error occurred when processing your file.";
                    break;
            }
        }

        if (empty($registerErrors)) {
            $input["password"] = password_hash($input["password"], PASSWORD_DEFAULT, ["cost" => 13]);
            try {
                $query = "INSERT INTO user_credentials SET username = ?, password = ?, email = ?";
                $stmt = $conn->prepare($query);
                // Role defaults to user
                $stmt->bindValue(1, $input["username"]);
                $stmt->bindValue(2, $input["password"]);
                $stmt->bindValue(3, $input["email"]);
                if (!$stmt->execute()) {
                    $registerErrors[] = "An unexpected error occurred, please try again later.";
                    goto fail;
                }
                // get the user's id to link to the details table
                $query = "SELECT id FROM user_credentials WHERE username = ? LIMIT 1";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $input["username"]);
                if (!$stmt->execute()) {
                    $registerErrors[] = "An unexpected error occurred, please try again later.";
                    goto fail;
                }
                $u = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $registerErrors[] = "An unexpected error occurred, please try again later.";
                goto fail;
            }
            if ($u === false) {
                throw new Exception("A strange error has occurred, please contact the administrators.");
            }
            $newUserId = (int) $u["id"];
            try {
                $query = "INSERT INTO user_details SET userID = ?, firstName = ?, lastName = ?, iconName = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $newUserId, PDO::PARAM_INT);
                $stmt->bindValue(2, $input["firstName"]);
                $stmt->bindValue(3, $input["lastName"]);
                $stmt->bindValue(4, $image_name);
                if (!$stmt->execute()) {
                    goto removeUser;
                }
                $genericMessages[] = "Registered successfully.";
            } catch (Exception $e) {
                removeUser:
                // uh oh, we couldn't add the details, let's remove the credentials to avoid a desync.
                try {
                    $query = "DELETE FROM user_credentials WHERE id = ? LIMIT 1";
                    $stmt = $conn->prepare($query);
                    $stmt->bindValue(1, $newUserId, PDO::PARAM_INT);
                    if (!$stmt->execute()) {
                        goto failedToRemoveUser;
                    }
                } catch (Exception $e) {
                    failedToRemoveUser:
                    // A very bad thing happened.
                    throw new Exception("A fatal error has occurred, please contact the administrators.");
                }
            }
        }
    } else {
        $genericMessages[] = "I know you like to mess with the HTML, believe me I do too, but can you <em>not</em> do that right now?";
    }
    fail:
    $hasLoginErrors = !empty($loginErrors);
    $hasRegisterErrors = !empty($registerErrors);
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
        <?php
        if (count($genericMessages) > 0) {
            echo "<div class=\"alert alert-info\">";
            foreach ($genericMessages as $message) {
                echo "<p>";
                echo $message;
                echo "</p>";
            }
            echo "</div>";
        }
        ?>
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
                <?php
                if ($hasLoginErrors) {
                    echo "<div class=\"alert alert-danger\">";
                    echo "Oops...";
                    echo "<ul>";
                    foreach ($loginErrors as $error) {
                        echo "<li>";
                        echo $error;
                        echo "</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                }

                ?>
                <form method="post">
                    <input type="hidden" name="formType" value="login">
                    <div class="m-2">
                        <label for="usernameL" class="form-label">User name</label>
                        <input name="username" id="usernameL" class="form-control"
                            value="<?= $hasRegisterErrors ? $input["username"] : "" ?>" minlength="5" maxlength="30"
                            autocomplete="username" required>
                    </div>
                    <div class="m-2">
                        <label for="passwordL" class="form-label">Password</label>
                        <input type="password" id="passwordL" name="password" class="form-control" minlength="8"
                            maxlength="60" autocomplete="current-password" required>
                    </div>
                    <div class="d-flex flex-row-reverse">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
            <div id="registerContainer" hidden>
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
                <form enctype="multipart/form-data" method="post">
                    <input type="hidden" name="formType" value="register">
                    <div class="columns-2">
                        <div class="m-2">
                            <label for="firstNameR" class="form-label">First name</label>
                            <input name="firstName" id="firstNameR" class="form-control"
                                value="<?= $hasRegisterErrors ? $input["firstName"] : "" ?>" minlength="2"
                                maxlength="50" autocomplete="given-name" required>
                        </div>
                        <div class="m-2">
                            <label for="lastNameR" class="form-label">Last name</label>
                            <input name="lastName" id="lastNameR" class="form-control"
                                value="<?= $hasRegisterErrors ? $input["lastName"] : "" ?>" minlength="2" maxlength="50"
                                autocomplete="family-name" required>
                        </div>
                        <div class="m-2">
                            <label for="usernameR" class="form-label">User name</label>
                            <input name="username" id="usernameR" class="form-control"
                                value="<?= $hasRegisterErrors ? $input["username"] : "" ?>" minlength="5" maxlength="30"
                                autocomplete="username" required>
                        </div>
                        <div class="m-2">
                            <label for="passwordR" class="form-label">Password</label>
                            <input type="password" id="passwordR" name="password" class="form-control" minlength="8"
                                maxlength="60" autocomplete="new-password" required>
                        </div>
                        <div class="m-2">
                            <label for="emailR" class="form-label">Email</label>
                            <input name="email" id="emailR" class="form-control"
                                value="<?= $hasRegisterErrors ? $input["email"] : "" ?>" minlength="5" maxlength="50"
                                autocomplete="email" required>
                        </div>
                        <div class="m-2">
                            <label for="iconR" class="form-label">Profile icon</label>
                            <input type="file" id="iconR" name="icon" class="form-control"
                                accept=".png, .jpg, .jpeg, .gif">
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
    let loginOpen = <?= (isset($_POST["formType"]) && $_POST["formType"] == "register") ? "false" : "true"; ?>;
</script>
<script src='loginAndRegister.js'></script>

</html>