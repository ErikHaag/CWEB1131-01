<?php
session_start();
$_SESSION = [];
session_unset();
session_destroy();
setcookie(session_name(), "", time() - 3600, "/");
header("Location: index.php");
exit();
?>