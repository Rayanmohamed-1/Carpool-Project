<?php
ob_start();
session_start();
require 'setup.php';

$message = "";

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $message = "Invalid verification link.";
} else {
    $token = $_GET['token'];

    // find user with this token who hasnt verified yet
    $verify = $conn->prepare("SELECT id, full_name, role FROM users WHERE verification_token = ? AND is_verified = 0");
    $verify->bind_param("s", $token);
    $verify->execute();
    $result = $verify->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // mark them as verified
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $update->bind_param("s", $token);

        if ($update->execute()) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $message = "success";
        }
        $update->close();
    } else {
        $message = "Invalid or expired verification link.";
    }

    $verify->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardiff Met Carpool - Email Verified</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0b3c5d, #092c45);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 440px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 5px;
            background: linear-gradient(90deg, #0b3c5d, #f2c94c);
        }
        .icon {
            font-size: 50px;
            margin-bottom: 16px;
        }
        h2 {
            color: #0b3c5d;
            font-size: 22px;
            margin-bottom: 12px;
        }
        p {
            color: #666;
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 13px 32px;
            background: #0b3c5d;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
        }
        .btn:hover { background: #092c45; }
        .error-msg { color: #843c0c; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($message === "success"): ?>
            <div class="icon">✅</div>
            <h2>Email Verified!</h2>
            <p>Your Cardiff Met email has been verified successfully. You can now log in and start carpooling.</p>
            <a href="index.html" class="btn">Log In Now</a>
        <?php else: ?>
            <div class="icon">❌</div>
            <h2>Verification Failed</h2>
            <p class="error-msg"><?= htmlspecialchars($message) ?></p>
            <a href="register-passenger.html" class="btn">Register Again</a>
        <?php endif; ?>
    </div>
</body>
</html>