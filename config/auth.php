<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Base URL of the project, worked out from where this file lives on disk
 * rather than hardcoded. Links and redirects keep working no matter what
 * the project folder is named or renamed to later
 * (e.g. "restaurant", "restaurant-fixed", "restaurant 2", ...).
 */
if (!defined('BASE_URL')) {
    $project_root = dirname(__DIR__);                                  // .../restaurant
    $doc_root     = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    $rel          = str_replace('\\', '/', substr($project_root, strlen($doc_root)));
    $rel          = '/' . trim($rel, '/');
    define('BASE_URL', ($rel === '/' ? '' : $rel) . '/');
}

function require_login()
{
    if (!isset($_SESSION['user'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

function require_role($role)
{
    require_login();

    if ($_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>Access Denied</title>
        <link rel='stylesheet' href='" . BASE_URL . "assets/css/style.css'></head>
        <body><div class='container'><div class='card' style='text-align:center;margin-top:60px'>
        <h2>⛔ Access Denied</h2>
        <p>You do not have permission to view this page.</p>
        <a class='btn' href='" . BASE_URL . "login.php'>Back to Login</a>
        </div></div></body></html>";
        exit;
    }
}

function redirect_dashboard()
{
    $role = $_SESSION['user']['role'];

    if ($role === 'admin') {
        header("Location: " . BASE_URL . "admin/dashboard.php");
    } elseif ($role === 'manager') {
        header("Location: " . BASE_URL . "manager/dashboard.php");
    } elseif ($role === 'customer') {
        header("Location: " . BASE_URL . "customer/dashboard.php");
    } elseif ($role === 'kitchen') {
        header("Location: " . BASE_URL . "kitchen/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "login.php");
    }

    exit;
}
