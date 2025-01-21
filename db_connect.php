<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//localhost
// $servername = "localhost:3307";
// $username = "root";
// $password = "mysql@preet2549c1c9";
// $dbname = "student_club";

//infinityfree.net
$servername = "sql206.infinityfree.com";
$username = "if0_38126047";
$password = "tatvaorg123";
$dbname = "if0_38126047_student_club";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    // echo "Connected successfully";
}
?>