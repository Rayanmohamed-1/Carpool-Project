<?php
session_start();
include 'setup.php';

$message = [];

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "driver") {
    header("Location: home.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

if (isset($_POST['Update_Profile'])) {

    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $car_make_model = mysqli_real_escape_string($conn, $_POST['car_make_model']);
    $car_registration = mysqli_real_escape_string($conn, $_POST['car_registration']);
    $car_colour = mysqli_real_escape_string($conn, $_POST['car_colour']);
    $seats_available = (int) $_POST['seats_available'];

    if (empty($phone) || empty($car_make_model) || empty($car_registration) || empty($car_colour) || empty($seats_available)) {
        $message[] = "Please fill out all fields.";
    } elseif (!preg_match('/^07[0-9]{9}$/', $phone)) {
        $message[] = "Please enter a valid UK mobile number.";
    } else {
        $update = mysqli_query($conn, "
            UPDATE drivers 
            SET 
                phone='$phone',
                car_make_model='$car_make_model',
                car_registration='$car_registration',
                car_colour='$car_colour',
                seats_available='$seats_available'
            WHERE user_id=$user_id
        ");

        if ($update) {
            $message[] = "Profile updated successfully.";
        } else {
            $message[] = "Profile could not be updated: " . mysqli_error($conn);
        }
    }
}

$user_result = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$user = mysqli_fetch_assoc($user_result);

$driver_result = mysqli_query($conn, "SELECT * FROM drivers WHERE user_id=$user_id");
$driver = mysqli_fetch_assoc($driver_result);

if (!$driver) {
    $driver = [
        'phone' => '',
        'car_make_model' => '',
        'car_registration' => '',
        'car_colour' => '',
        'seats_available' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="profile.css">
</head>

<body class="driver-dashboard-profile">
<nav class="nav">
    <a href="Driverdashboard.php">Dashboard</a>
</nav>

<div class="page-center">
    <div class="card">

        <h2>My Profile</h2>

        <?php 
        foreach ($message as $msg) {
            echo '<span class="message">' . htmlspecialchars($msg) . '</span>';
        }
        ?>

        <form action="profile.php" method="post">

            <label>Full Name:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" readonly>

            <label>Email:</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>

            <label>Student ID:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" readonly>

            <label>Faculty:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['faculty'] ?? ''); ?>" readonly>

            <label>Phone Number:</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($driver['phone'] ?? ''); ?>" pattern="07[0-9]{9}" maxlength="11" minlength="11" required>

            <label>Car Make & Model:</label>
            <input type="text" name="car_make_model" value="<?php echo htmlspecialchars($driver['car_make_model'] ?? ''); ?>" required>

            <label>Car Registration:</label>
            <input type="text" name="car_registration" value="<?php echo htmlspecialchars($driver['car_registration'] ?? ''); ?>" required>

            <label>Car Colour:</label>
            <input type="text" name="car_colour" value="<?php echo htmlspecialchars($driver['car_colour'] ?? ''); ?>" required>

            <label>Seats Available:</label>
            <input type="number" name="seats_available" min="1" max="6" value="<?php echo htmlspecialchars($driver['seats_available'] ?? ''); ?>" required>

            <input type="submit" name="Update_Profile" value="Update Profile">
        </form>

    </div>
</div>

</body>
</html>