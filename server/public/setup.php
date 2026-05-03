<?php 
$host = "db";
$username = "root";
$password = "root";
$database = "carpool";

$conn = new mysqli($host,$username,$password,$database);
if ($conn ->connect_error) {
    die("$database" . $conn ->connect_error);
}
?>