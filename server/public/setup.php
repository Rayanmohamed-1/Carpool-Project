<?php 
$host = "db";
$username = "root";
$password = "root";
$database = "carpool";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
?>