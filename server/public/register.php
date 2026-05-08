<?php
ob_start();
// connect to the database
require 'setup.php';

// tell the browser we are sending back json
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// get the data sent from the registration form
$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$student_id = trim($data['student_id'] ?? '');
$faculty = trim($data['faculty'] ?? '');
$phone = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? '';

// check its a cardiff met email address
if (!str_ends_with($email, '@cardiffmet.ac.uk') && !str_ends_with($email, '@outlook.uwicac.ac.uk')) {
    http_response_code(400);
    echo json_encode(['error' => 'You must use a Cardiff Met email address.']);
    exit;
}
// make sure none of the fields are empty
if (empty($name) || empty($email) || empty($password) || empty($role) || empty($student_id) || empty($faculty) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

// password has to be at least 8 characters
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters.']);
    exit;
}

// role can only be passenger or driver
if ($role != 'passenger' && $role != 'driver') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role selected.']);
    exit;
}

// check if someone already registered with this email
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'An account with this email already exists.']);
    exit;
}

// also check student id isnt already taken
$checkId = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
$checkId->bind_param("s", $student_id);
$checkId->execute();
$checkId->store_result();

if ($checkId->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'This student ID is already registered.']);
    exit;
}

// hash the password - never store plain text
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// generate a random token for email verification
// same approach as the driver registration
$token = bin2hex(random_bytes(32));

// save the new user to the database with the verification token
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, student_id, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
$stmt->bind_param("ssssss", $name, $email, $hashedPassword, $role, $student_id, $token);

if ($stmt->execute()) {
    // build the verify link using the same verifyemail.php rayan made
    $verifyLink = "http://localhost:8080/verifyemail.php?token=" . $token;
    http_response_code(201);
    echo json_encode([
        'message' => 'Account created successfully.',
        'verify_link' => $verifyLink
    ]);
} else {
    // something went wrong with the database
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed. Please try again.']);
}

$conn->close();
?>