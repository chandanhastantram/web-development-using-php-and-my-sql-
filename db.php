<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$server = "localhost";
$user = "root";
$pass = "";
$dbname = "blogpostdb";

// Create connection
$conn = new mysqli($server, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die( $conn->connect_error);
}  
?>

