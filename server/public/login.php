<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// connect to database
require 'setup.php';

// send back json
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// get the email and password from the login form
$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

// check fields arent empty
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

// make sure its a cardiff met email
if (!str_ends_with($email, '@cardiffmet.ac.uk') && !str_ends_with($email, '@outlook.uwicac.ac.uk')) {
    http_response_code(400);
    echo json_encode(['error' => 'You must use a Cardiff Met email address.']);
    exit;
}

// look up the user in the database by email
// make sure we get is_verified too
$stmt = $conn->prepare("SELECT id, full_name, email, password, role, is_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// check if user exists and the password is correct
if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email or password.']);
    exit;
}

// block login if email not verified yet
if ($user['is_verified'] == 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Please verify your email before logging in.']);
    exit;
}

// start session before any output
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_email'] = $user['email'];

// send back success and the role so the page knows where to redirect
http_response_code(200);
echo json_encode([
    'message' => 'Login successful.',
    'role' => $user['role'],
    'name' => $user['full_name']
]);

$conn->close();
?>