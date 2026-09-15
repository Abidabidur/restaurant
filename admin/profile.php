<?php
require "../config/database.php";
require "../config/auth.php";
require_role("admin");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_admin.php"; ?>
    <div class="main">
        <div class="top-bar"><h1>My Profile</h1><span class="badge-role">Administrator</span></div>
        <div class="content">
            <?php require "../includes/profile_form.php"; ?>
        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
