<?php
require "config/dbConfig.php";
require "utils.php";

// validate identity
session_start();

[$id, $role] = get_role($conn, $_SESSION["username"] ?? "", $_SESSION["password"] ?? "");
if ($role == "none") {
    if ($id == -2) {
        http_response_code(503);
    } else {
        http_response_code(401);
        header("Location: logout.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // users can only edit themselves
    if ($role == "user" && $_GET["username"] != $_SESSION["username"]) {
        http_response_code(403);
        header("Location: dashboard.php");
        exit();
    }
    // validate username to edit
    $editUsername = [$_GET["username"]];
    $errors = validate($editUsername, ["username"], [""]);
    if (!empty($errors)) {
        http_response_code(400);
        header("Location: dashboard.php");
        exit();
    }
    fillForm:
    try {
        $query = "SELECT c.email, d.firstName, d.lastName, d.iconName FROM user_credentials as c JOIN user_details as d ON c.id = d.userId WHERE c.username = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $editUsername[0]);
        if (!$stmt->execute()) {
            echo "Oops...";
            exit(1);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "Oops...";
    }
    if ($row == false) {
        header("Location: dashboard.php");
        exit();
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // users can only edit themselves
    if ($role == "user" && $_GET["username"] != $_SESSION["username"]) {
        http_response_code(403);
        header("Location: dashboard.php");
        exit();
    }
    // validate username to edit
    $editUsername = [$_GET["username"]];
    $errors = validate($editUsername, ["username"], [""]);
    if (!empty($errors)) {
        http_response_code(400);
        header("Location: dashboard.php");
        exit();
    }
    $inputs = [$_POST["firstName"], $_POST["lastName"], $_POST["email"]];
    $editErrors = validate($inputs, ["name", "name", "email"], ["First name", "Last name", ""]);
    if (!empty($editErrors)) {
        goto fillForm;
    }
    $iconName = "";
    switch ($_POST["iconChoice"]) {
        case "keep":
            break;
        case "default":
            $iconName = "icons/standard_user.png";
            break;
        case "new":
            if (!isset($_FILES["newIcon"])) {
                $editErrors[] = "New icon is missing";
                goto fillForm;
            }
            switch ($_FILES["newIcon"]["error"]) {
                case 0:
                    // Handle image
                    $tempName = $_FILES["newIcon"]["tmp_name"];
                    $contentType = mime_content_type($tempName);
                    if (is_uploaded_file($tempName)) {
                        $iconName = "icons/u_" . $_GET["username"];
                        switch ($contentType) {
                            case "image/png":
                                $iconName .= ".png";
                                break;
                            case "image/jpeg":
                                $iconName .= ".jpeg";
                                break;
                            case "image/gif":
                                $iconName .= ".gif";
                            default:
                                $editErrors[] = "Invalid file type";
                                goto fillForm;
                        }
                        if (!move_uploaded_file($tempName, $iconName)) {
                            // ah come on.
                            $iconName = "";
                        }
                    }
                    break;
                case 1:
                case 2:
                    $editErrors[] = "Your file bigger than 40 Mebibytes";
                    goto fillForm;
                case 4:
                    goto fillForm;
                default:
                    $editErrors[] = "An unexpected error occurred when processing your file.";
                    goto fillForm;
            }
            break;
        default:
            $editErrors[] = "Invalid icon action";
            goto fillForm;
    }

    try {
        $query = "SELECT id FROM user_credentials WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $_GET["username"]);
        if (!$stmt->execute()) {
            $editErrors[] = "An unexpected issue occurred!";
            goto fillForm;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $editErrors[] = "An unexpected issue occurred!";
        goto fillForm;
    }

    if ($row == false) {
        $editErrors[] = "An unexpected issue occurred!";
        goto fillForm;
    }
    $editId = $row["id"];
    try {
        $query = "UPDATE user_details SET firstName = :fn, lastName = :ln";
        if ($iconName != "") {
            $query .= ", iconName = :icon";
        }
        $query .= " WHERE userId = :id LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue("fn", $inputs[0]);
        $stmt->bindValue("ln", $inputs[1]);
        if ($iconName != "") {
            $stmt->bindValue("icon", $iconName);
        }
        $stmt->bindValue("id", $editId, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            $editErrors[] = "An unexpected issue occurred!";
            goto fillForm;
        }
    } catch (Exception $e) {
        goto fillForm;
    }
    header("Location: dashboard.php");
} else {
    http_response_code(405);
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
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
</head>

<body>
    <div class="container mt-5 mx-5">
        <?php
        if (isset($editErrors) && !empty($editErrors)) {
            echo "<div class=\"alert alert-danger\">";
            echo "<ul>";
            foreach ($editErrors as $error) {
                echo "<li>";
                echo $error;
                echo "</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        ?>
        <form enctype="multipart/form-data" method="post">
            <div class="columns-2">
                <div class="m-2">
                    <label for="firstName" class="form-label">First name</label>
                    <input name="firstName" id="firstName" class="form-control" value="<?= $row["firstName"] ?>"
                        minlength="2" maxlength="50" autocomplete="given-name" required>
                </div>
                <div class="m-2">
                    <label for="lastName" class="form-label">Last name</label>
                    <input name="lastName" id="lastName" class="form-control" value="<?= $row["lastName"] ?>"
                        minlength="2" maxlength="50" autocomplete="family-name" required>
                </div>
                <div class="m-2">
                    <label for="email" class="form-label">Email</label>
                    <input name="email" id="email" class="form-control" value="<?= $row["email"] ?>" minlength="5"
                        maxlength="50" autocomplete="email" required>
                </div>
                <div class="m-2">
                    <select id="iconChoice" name="iconChoice" required>
                        <option value="keep" selected>Keep icon</option>
                        <option value="default">Remove icon</option>
                        <option value="new">Change icon</option>
                    </select>
                    <input type="file" id="icon" name="newIcon" disabled>
                </div>
            </div>
            <div class="d-flex flex-row-reverse">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="dashboard.php" class="btn btn-secondary me-2">Go back</a>
            </div>
        </form>
    </div>
</body>
<script src="edit.js"></script>

</html>