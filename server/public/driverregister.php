here is my driver registration php file: <?php
session_start();
include("setup.php");
$message = "";
$verify_link = "";
$registration_success = false;
 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, "Firstname", FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $student_id = filter_input(INPUT_POST, "StudentID", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $faculty = filter_input(INPUT_POST, "faculty", FILTER_SANITIZE_SPECIAL_CHARS);
    $phone = filter_input(INPUT_POST, "phone", FILTER_SANITIZE_SPECIAL_CHARS);
    $car_make_model = filter_input(INPUT_POST, "car_make_model", FILTER_SANITIZE_SPECIAL_CHARS);
    $car_registration = filter_input(INPUT_POST, "car_registration", FILTER_SANITIZE_SPECIAL_CHARS);
    $car_colour = filter_input(INPUT_POST, "car_colour", FILTER_SANITIZE_SPECIAL_CHARS);
    $seats_available = filter_input(INPUT_POST, "seats_available", FILTER_SANITIZE_NUMBER_INT);
 
    if (empty($name)) {
        echo "Please enter your name:";
    } elseif (empty($email)) {
        echo "Please enter your email:";
    } elseif (empty($student_id)) {
        echo "Please enter your Student ID:";
    } elseif (empty($password)) {
        echo "Please enter your password:";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}$/', $password)) {
        echo "The password must have a capital letter, a number and must be 8 characters long";
    } elseif ($password !== $confirm_password) {
        echo "Unfortunately the passwords do not match. Please try this again!";
    }elseif (!preg_match('/^07[0-9]{9}$/', $phone)) {
        echo "Please enter a valid UK mobile number:";
    } else {
 
        $check = mysqli_query($conn, "SELECT id FROM users WHERE student_id='$student_id'");
        if (mysqli_num_rows($check) > 0) {
            echo "This Student ID is already registered. Please use a different Student ID or log in ;).";
            exit();
        }
        $image_file = $_FILES["drivers_licence_image"];
 
if (!isset($image_file) || $image_file["error"] !== 0) {
    die('No file uploaded.');
}
if (filesize($image_file["tmp_name"]) <= 0) {
    die('Uploaded file is empty.');
}
$image_type = exif_imagetype($image_file["tmp_name"]);
if (!$image_type) {
    die('File is not a valid image.');
}
$image_extension = image_type_to_extension($image_type, true);
$image_name = bin2hex(random_bytes(16)) . $image_extension;
$upload_dir = __DIR__ . "/uploads/licences/";
 
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$full_path = $upload_dir . $image_name;
$drivers_licence_image = "uploads/licences/" . $image_name;
if (!move_uploaded_file($image_file["tmp_name"], $full_path)) {
    die("Failed to upload image :(");
}
 
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
 
        $sql = "INSERT INTO users
        (full_name, email, password, student_id, role, verification_token, is_verified)
        VALUES
        ('$name', '$email', '$hash', '$student_id', 'driver', '$token', 0)";
 
        $result = mysqli_query($conn, $sql);
 
        if ($result) {
            $user_id = mysqli_insert_id($conn);
 
            $sql = "INSERT INTO drivers
             (user_id, faculty, phone, car_make_model, car_registration, car_colour, seats_available, drivers_licence_image)
             VALUES
             ('$user_id', '$faculty', '$phone', '$car_make_model', '$car_registration', '$car_colour', '$seats_available','$drivers_licence_image')";
 
            $result = mysqli_query($conn, $sql);
 
            if ($result) {
                $verify_link = "http://localhost:8080/verifyemail.php?token=" . $token;
                    
                    $registration_success = true;
                    $message = "Registration is successful! Please verify your email to activate your account.";
               
                } else {
                echo "You are officially registered!";
            }
        } else {
            echo "Unfortunately, this isn't working! Please double check and fill the form in correctly ;)";
        }
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="driverregister.css">
    <title>Driver Registration Zone</title>
</head>
 
<body class="Driver">
<div class="page-center">
    <div class="card">
        <h2>Driver Registration Zone</h2>
 
        <?php if ($registration_success): ?>
            <div class="success-message">
                <p><?= $message ?></p>
                <a href="<?= $verify_link ?>">Verify Email</a>
            </div>
            <?php endif; ?>
    
        <form action="http://localhost:8080/driverregister.php" method="post" enctype="multipart/form-data">
            <div class="form-section">
                <h3>Personal Details</h3>
                <label>Full Name:</label>
                <input type="text" name="Firstname" required>
                <label>Email:</label>
                <input type="email" name="email" required>
                <label>Student ID:</label>
                <input type="text" name="StudentID" required>
                <label>Password:</label>
                <input type="password" name="password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}" title="Please enter a password that has a minimum of 8 characters, has a capital letter, a lowercase letter, and a number." required>
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required>
                <label>Faculty:</label>
                <input type="text" name="faculty">
                <label>Phone:</label>
                <input type="tel" id="phone" name="phone" pattern="07[0-9]{9}" maxlength="11" minlength="11" title= "Please enter a valid UK phone number that starts with 07." required>
            </div>
            <div class="form-section">
                <h3>Car Details</h3>
                <label>Car Make & Model:</label>
                <input type="text" name="car_make_model">
                <label>Car Registration:</label>
                <input type="text" name="car_registration">
                <label>Car Colour:</label>
                <input type="text" name="car_colour">
                <label>Seats Available:</label>
                <input type="number" name="seats_available" min="1" max="6">
            </div>
            <div class="form-section-upload">
                <h3>Driver's Licence</h3>
                <label>Upload Driving Licence:</label>
                <input type="file" name="drivers_licence_image" accept="image/*" required>
            </div>
            <input type="submit" value="Register">   
        </form>
    </div>
</div>
</body>
</html> verifyemail.php - <?php
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
