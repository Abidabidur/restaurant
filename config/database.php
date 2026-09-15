<?php
<<<<<<< HEAD
/**
 * Database connection (MAMP / XAMPP friendly).
 *
 * MAMP on macOS runs MySQL on port 8889, which "localhost" alone does not
 * reach (PHP tries the default local socket instead and the connection
 * fails with "No such file or directory"). We try the common local setups
 * in order and use the first one that answers, so this keeps working
 * whether you're on MAMP, XAMPP, or plain local MySQL.
 */

$db_name = "restaurant_db";

// [host, port, user, password] — tried in this order
$db_candidates = [
    ["127.0.0.1", 8889, "root", "root"],   // MAMP (Mac) default
    ["127.0.0.1", 3306, "root", "root"],   // MAMP with the standard port
    ["127.0.0.1", 3306, "root", ""],       // XAMPP / WAMP default
];

$conn        = null;
$last_error  = "";

foreach ($db_candidates as [$host, $port, $user, $pass]) {
    $try = @new mysqli($host, $user, $pass, $db_name, $port);
    if (!$try->connect_error) {
        $conn = $try;
        break;
    }
    $last_error = $try->connect_error;
}

if (!$conn) {
    die(
        "<div style='font-family:sans-serif;max-width:560px;margin:60px auto;line-height:1.6'>
         <h2>Database connection failed</h2>
         <p style='color:#a33'>" . htmlspecialchars($last_error) . "</p>
         <p>Checklist:</p>
         <ol>
            <li>Is MySQL running (MAMP / XAMPP)?</li>
            <li>Has <code>database/restaurant.sql</code> been imported? It creates the <code>restaurant_db</code> database.</li>
            <li>If your MySQL port or password is different, edit
                <code>config/database.php</code> and add it to the <code>\$db_candidates</code> list.</li>
         </ol>
         </div>"
    );
}

$conn->set_charset("utf8mb4");
=======

$host = "localhost";
$username = "root";
$password = "root";
$database = "restaurant_db";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
