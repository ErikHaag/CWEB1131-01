<?php
require "config/dbconfig.php";
$registering = false;
$message = "";
// Yoinked from week 6 because I like it.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (($_POST["type"] ?? "") == "login") {
        $inputs = filter_assoc($_POST, ["user_name", "password"]);
        $errors = sanitize($inputs,  "l_");
        if (empty($errors)) {
            if (strlen($inputs["user_name"]) > 50 || preg_match($regexes["user_name"], $inputs["user_name"]) != 1 && preg_match($regexes["email"], $inputs["user_name"]) != 1) {
                $errors["r_user_name"] = "Invalid user name";
            }
            if (preg_match($regexes["password"], $inputs["password"]) != 1) {
                $errors["password"] = "Invalid password";
            }
        }
        if (empty($errors)) {
            try {
                $query = "SELECT id, user_name, password, role FROM users WHERE user_name = ? OR email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $inputs["user_name"]);
                $stmt->bindValue(2, $inputs["user_name"]);
                if ($stmt->execute()) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user != false && password_verify($inputs["password"], $user["password"])) {
                        session_start();
                        $_SESSION["user_id"] = $user["id"];
                        $_SESSION["user_name"] = $user["user_name"];
                        $_SESSION["password"] = $inputs["password"];
                        header("Location: dashboard.php");
                    } else {
                        $errors["l_misc"] = "Incorrect username, email, or password.";
                    }
                } else {
                    $errors["l_misc"]  = "An error occurred!";
                }
            } catch (Exception $e) {
                $errors["l_misc"]  = "An error occurred!";
            }
        }
    } else if (($_POST["type"]  ?? "") == "register") {
        $registering = true;
        $inputs = filter_assoc($_POST, ["full_name", "user_name", "email", "password"]);
        $errors = sanitize($inputs,  "r_");
        if (empty($errors)) {
            // scrub the inputs with soap, aloe, shampoo, olive oil, parrafin, constantan, swiss cheese, and livermorium; for flavor.
            if (strlen($inputs["full_name"]) > 100 || preg_match($regexes["full_name"], $inputs["full_name"]) != 1) {
                $errors["r_full_name"] = "Invalid name, up to 100 english letters or spaces are allowed";
            }
            if (strlen($inputs["user_name"]) > 50 || preg_match($regexes["user_name"], $inputs["user_name"]) != 1) {
                $errors["r_user_name"] = "Invalid user name, up to 50 letters, numbers, or underscores are allowed";
            }
            if (strlen($inputs["email"]) > 50 || preg_match($regexes["email"], $inputs["email"]) != 1) {
                $errors["r_email"] = "Invalid email"; 
            }
            if (preg_match($regexes["password"], $inputs["password"]) != 1) {
                $errors["password"] = "Invalid password, between 5 and 50 letters, digits, or the following symbols ~!@#$%^&*()[]{}_-+=\\|/`'\";:<>,.? and allowed";
            }
        }
        if (empty($errors)) {
            $role = "user";
            $inputs["password"] = password_hash($inputs["password"], PASSWORD_DEFAULT, ["cost" => 13]);
            try {
                $query = "INSERT INTO users SET full_name = ?, user_name = ?, email = ?, password = ?, role = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $inputs["full_name"]);
                $stmt->bindValue(2, $inputs["user_name"]);
                $stmt->bindValue(3, $inputs["email"]);
                $stmt->bindValue(4, $inputs["password"]);
                $stmt->bindValue(5, $role);
                if ($stmt->execute()) {
                    $message = "Successfully registered!";
                } else {
                    $errors["r_misc"]  = "An error occurred!";
                }
            } catch (Exception $e) {
                $errors["r_misc"]  = "An error occurred!";
            }
        }
        } else {
        echo "<p>Dude, you don't need to change every little thing.</p>";
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Log in</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <!-- <script src='main.js'></script> -->
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg p-4">
                    <ul class="nav nav-tabs" id="authTabs">
                        <li class="nav-item">
                            <a class="nav-link<?php echo !$registering ? " active" : ""?>" data-bs-toggle="tab" href="#login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $registering ? " active" : ""?>" data-bs-toggle="tab" href="#register">Register</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3">
                        <div class="tab-pane fade<?php echo !$registering ? " show active" : ""?>" id="login">
                            <form method="post">
                                <input type="hidden" name="type" value="login">
                                <div class="mb-3">
                                    <label class="form-label">User name</label>
                                    <input type="text" class="form-control" name="user_name">
                                    <p class="text-danger"><?php echo $errors["l_user_name"] ?? "";?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password">
                                    <p class="text-danger"><?php echo $errors["l_password"] ?? "";?></p>
                                </div>
                                <p class="text-danger"><?php echo $errors["l_misc"] ?? ""; ?></p>
                                <input type="submit" class="btn btn-primary w-100" value="Login">
                            </form>
                        </div>
                        <div class="tab-pane fade<?php echo $registering ? " show active" : ""?>" id="register">
                            <form method="post">
                                <input type="hidden" name="type" value="register">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name">
                                    <p class="text-danger"><?php echo $errors["r_full_name"] ?? "";?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">User Name</label>
                                    <input type="text" class="form-control" name="user_name">
                                    <p class="text-danger"><?php echo $errors["r_user_name"] ?? "";?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" name="email">
                                    <p class="text-danger"><?php echo $errors["r_email"] ?? "";?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password">
                                    <p class="text-danger"><?php echo $errors["r_password"] ?? "";?></p>
                                </div>
                                <p class="text-danger"><?php echo $errors["r_misc"] ?? ""; ?></p>
                                <p class="text-primary"><?php echo $message ?? ""; ?></p>
                                <input type="submit" class="btn btn-primary w-100" value="Register">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
    crossorigin="anonymous"></script>

</html>