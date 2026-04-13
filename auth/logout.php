<?php
// auth/logout.php — Destroys session and redirects to login
session_start();
session_unset();
session_destroy();
header("Location: /pos-system/auth/login.php");
exit;
