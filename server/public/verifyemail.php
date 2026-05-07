<?php
session_start();
include("setup.php");
$message = "";

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid verification link.");
}

$token = $_GET['token'];

$verify = $conn->prepare("SELECT id, full_name FROM users WHERE verification_token = ? AND is_verified = 0");
$verify->bind_param("s", $token);
$verify->execute();
$result = $verify->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
    $update->bind_param("s", $token);

if ($update->execute()) {
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["name"] = $user["full_name"];
    $message = "Your email has been verified successfully!";
    
        header("Refresh: 3; url=Driverdashboard.php");
    }
    $update->close();
} else {
    $message = "<span class='error'>Invalid or expired verification link.</span>";
}

$verify->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="verifyemail.css">
</head>

<body class="Driver">
    <div class="page-center">
        <div class="card">
            <h2>Email Verification</h2>

            <div class="verification-message">
                <?= $message ?>
            </div>
        </div>
    </div>
</body>
</html>