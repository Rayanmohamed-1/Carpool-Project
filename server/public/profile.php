<?php
session_start();
include 'setup.php';

$message = [];
$user_id = 1;

if (isset($_POST['Update_Profile'])) {

    $phone            = $_POST['phone'];
    $car_make_model   = $_POST['car_make_model'];
    $car_registration = $_POST['car_registration'];
    $car_colour       = $_POST['car_colour'];
    $seats_available  = (int) $_POST['seats_available'];

    if (empty($phone) || empty($car_make_model) || empty($car_registration) || empty($car_colour) || empty($seats_available)) {
        $message[] = "Please fill out all fields. It is really important to fill in the CORRECT information";
    } elseif (!preg_match('/^07[0-9]{9}$/', $phone)) {
        $message[] = "Please enter a valid UK mobile number:";

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
            $message[] = "Congrats, your profile updated successfully ;).";
        } else {
            $message[] = "Unfortunately, your profile could not be updated :(.";
        }
    }
}

$user   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));
$driver = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM drivers WHERE user_id=$user_id"));
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
        if (!empty($message)) {
            foreach ($message as $message) {
                echo '<span class="message">'.$message.'</span>';
            }
        }
        ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">

            <label>Full Name:</label>
            <input type="text" value="<?php echo $user['full_name']; ?>" readonly>
            <label>Email:</label>
            <input type="email" value="<?php echo $user['email']; ?>" readonly>
            <label>Student ID:</label>
            <input type="text" value="<?php echo $user['student_id']; ?>" readonly>
            <label>Faculty:</label>
            <input type="text" value="<?php echo $driver['faculty']; ?>" readonly>
            <label>Phone Number:</label>
            <input type="tel" id="phone" name="phone" pattern="07[0-9]{9}" maxlength="11" minlength="11" required>
            <label>Car Make & Model:</label>
            <input type="text" name="car_make_model" value="<?php echo $driver['car_make_model']; ?>" required>
            <label>Car Registration:</label>
            <input type="text" name="car_registration" value="<?php echo $driver['car_registration']; ?>" required>
            <label>Car Colour:</label>
            <input type="text" name="car_colour" value="<?php echo $driver['car_colour']; ?>" required>
            <label>Seats Available:</label>
            <input type="number" name="seats_available" min="1" max="6" value="<?php echo $driver['seats_available']; ?>" required>
            <input type="submit" name="Update_Profile" value="Update Profile">
        </form>

    </div>
</div>

</body>
</html>