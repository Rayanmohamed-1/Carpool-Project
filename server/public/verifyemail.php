<?php
session_start();
include("setup.php");

$message = "";

if (!isset($_GET["token"]) || empty($_GET["token"])) {
    $message = "Invalid verification link.";
} else {

    $token = $_GET["token"];

    $verify = $conn->prepare("
        SELECT id, full_name, role 
        FROM users 
        WHERE verification_token = ? 
        AND is_verified = 0
        AND role = 'driver'
    ");

    $verify->bind_param("s", $token);
    $verify->execute();

    $result = $verify->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        $update = $conn->prepare("
            UPDATE users 
            SET is_verified = 1, verification_token = NULL 
            WHERE id = ?
        ");

        $update->bind_param("i", $user["id"]);

        if ($update->execute()) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["full_name"];
            $_SESSION["user_role"] = "driver";

            header("Location: Driverdashboard.php");
            exit();

        } else {
            $message = "Email verification failed. Please try again.";
        }

        $update->close();

    } else {
        $message = "Invalid or expired verification link.";
    }

    $verify->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
</head>

<body>

    <h2>Email Verification</h2>

    <p><?= htmlspecialchars($message) ?></p>

    <a href="driverregister.php">Back to Driver Registration</a>

</body>
</html>