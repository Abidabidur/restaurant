<?php
<<<<<<< HEAD
require __DIR__ . "/config/auth.php";   // starts the session and defines BASE_URL
=======
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();
<<<<<<< HEAD
header("Location: " . BASE_URL . "login.php");
=======
header("Location: /restaurant 2/login.php");
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
exit;
