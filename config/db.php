<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pos_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:20px;background:#fee;border:1px solid red;'>
        <h3>❌ Database Connection Failed</h3>
        <p>" . $conn->connect_error . "</p>
    </div>");
}

$conn->set_charset("utf8");