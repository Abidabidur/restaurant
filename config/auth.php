<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login()
{
    if (!isset($_SESSION['user'])) {
        header("Location: /restaurant 2/login.php");
        exit;
    }
}

function require_role($role)
{
    require_login();

    if ($_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>Access Denied</title>
        <link rel='stylesheet' href='/restaurant 2/assets/css/style.css'></head>
        <body><div class='container'><div class='card' style='text-align:center;margin-top:60px'>
        <h2>⛔ Access Denied</h2>
        <p>You do not have permission to view this page.</p>
        <a class='btn' href='/restaurant 2/login.php'>Back to Login</a>
        </div></div></body></html>";
        exit;
    }
}

function redirect_dashboard()
{
    $role = $_SESSION['user']['role'];

    if ($role === 'admin') {
        header("Location: /restaurant 2/admin/dashboard.php");
    } elseif ($role === 'manager') {
        header("Location: /restaurant 2/manager/dashboard.php");
    } elseif ($role === 'customer') {
        header("Location: /restaurant 2/customer/dashboard.php");
    } elseif ($role === 'kitchen') {
        header("Location: /restaurant 2/kitchen/dashboard.php");
    } else {
        header("Location: /restaurant 2/login.php");
    }

    exit;
}
