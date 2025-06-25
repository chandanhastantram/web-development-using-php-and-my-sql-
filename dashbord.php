<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
} else {
    echo "Welcome to Dashboard, {$_SESSION['user_name']}<br>";
    echo "<a href='logout.php'>Logout</a>";
}
?>

