<?php 
$host = "db";
$username = "root";
$password = "root";
$database = "carpool";

// creating a connection.
$conn = new mysqli($host,$username,$password,$database);

// checking the connection.
if ($conn ->connect_error) {
    die("$database" . $conn ->connect_error);
}
echo "Connected successfully";
?>