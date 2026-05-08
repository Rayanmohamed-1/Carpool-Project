<?php
session_start();
include("setup.php");

$message = "";
$verify_link = "";
$registration_success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["Firstname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $student_id = trim($_POST["StudentID"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $faculty = trim($_POST["faculty"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $car_make_model = trim($_POST["car_make_model"] ?? "");
    $car_registration = trim($_POST["car_registration"] ?? "");
    $car_colour = trim($_POST["car_colour"] ?? "");
    $seats_available = (int)($_POST["seats_available"] ?? 0);

    if (
        empty($name) || empty($email) || empty($student_id) ||
        empty($password) || empty($confirm_password) || empty($phone)
    ) {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}$/', $password)) {
        $message = "Password must be at least 8 characters and include uppercase, lowercase, and a number.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (!preg_match('/^07[0-9]{9}$/', $phone)) {
        $message = "Please enter a valid UK mobile number.";
    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
        $check->bind_param("ss", $student_id, $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $message = "This Student ID or email is already registered.";
        } else {

            if (!isset($_FILES["drivers_licence_image"]) || $_FILES["drivers_licence_image"]["error"] !== 0) {
                $message = "Please upload your driving licence image.";
            } else {

                $image_file = $_FILES["drivers_licence_image"];
                $image_type = exif_imagetype($image_file["tmp_name"]);

                if (!$image_type) {
                    $message = "Uploaded file must be a valid image.";
                } else {

                    $image_extension = image_type_to_extension($image_type, true);
                    $image_name = bin2hex(random_bytes(16)) . $image_extension;

                    $upload_dir = __DIR__ . "/uploads/licences/";

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $full_path = $upload_dir . $image_name;
                    $drivers_licence_image = "uploads/licences/" . $image_name;

                    if (!move_uploaded_file($image_file["tmp_name"], $full_path)) {
                        $message = "Failed to upload image.";
                    } else {

                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $token = bin2hex(random_bytes(32));

                        $insert_user = $conn->prepare("
                            INSERT INTO users 
                            (full_name, email, password, faculty, student_id, role, verification_token, is_verified)
                            VALUES (?, ?, ?, ?, ?, 'driver', ?, 0)
                        ");

                        $insert_user->bind_param("ssssss", $name, $email, $hash, $faculty, $student_id, $token);

                        if ($insert_user->execute()) {

                            $user_id = $insert_user->insert_id;

                            $insert_driver = $conn->prepare("
                                INSERT INTO drivers
                                (user_id, phone, car_make_model, car_registration, car_colour, seats_available, drivers_licence_image)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");

                            $insert_driver->bind_param(
                                "issssis",
                                $user_id,
                                $phone,
                                $car_make_model,
                                $car_registration,
                                $car_colour,
                                $seats_available,
                                $drivers_licence_image
                            );

                            if ($insert_driver->execute()) {
                                $verify_link = "http://localhost:8080/verifyemail.php?token=" . $token;
                                $registration_success = true;
                                $message = "Registration successful. Please verify your email.";
                            } else {
                                $message = "Driver details could not be saved.";
                            }

                            $insert_driver->close();

                        } else {
                            $message = "Registration failed.";
                        }

                        $insert_user->close();
                    }
                }
            }
        }

        $check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Registration Zone</title>
    <link rel="stylesheet" href="driverregister.css">
</head>

<body class="Driver">

<div class="page-center">
    <div class="card">

        <h2>Driver Registration Zone</h2>

        <?php if (!empty($message)): ?>
            <div class="<?= $registration_success ? 'success-message' : 'error-message' ?>">
                <p><?= htmlspecialchars($message) ?></p>

                <?php if ($registration_success): ?>
                    <a href="<?= htmlspecialchars($verify_link) ?>">Verify Email</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$registration_success): ?>
        <form action="driverregister.php" method="post" enctype="multipart/form-data">

            <div class="form-section">
                <h3>Personal Details</h3>

                <label>Full Name:</label>
                <input type="text" name="Firstname" required>

                <label>Email:</label>
                <input type="email" name="email" required>

                <label>Student ID:</label>
                <input type="text" name="StudentID" required>

                <label>Password:</label>
                <input 
                    type="password" 
                    name="password" 
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}" 
                    title="Minimum 8 characters, with uppercase, lowercase, and a number." 
                    required
                >

                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required>

                <label>Faculty:</label>
                <input type="text" name="faculty">

                <label>Phone:</label>
                <input 
                    type="tel" 
                    name="phone" 
                    pattern="07[0-9]{9}" 
                    maxlength="11" 
                    minlength="11" 
                    title="Enter a valid UK phone number starting with 07." 
                    required
                >
            </div>

            <div class="form-section">
                <h3>Car Details</h3>

                <label>Car Make & Model:</label>
                <input type="text" name="car_make_model" required>

                <label>Car Registration:</label>
                <input type="text" name="car_registration" required>

                <label>Car Colour:</label>
                <input type="text" name="car_colour" required>

                <label>Seats Available:</label>
                <input type="number" name="seats_available" min="1" max="6" required>
            </div>

            <div class="form-section-upload">
                <h3>Driver's Licence</h3>

                <label>Upload Driving Licence:</label>
                <input type="file" name="drivers_licence_image" accept="image/*" required>
            </div>

            <input type="submit" value="Register">

        </form>
        <?php endif; ?>

    </div>
</div>

</body>
</html>