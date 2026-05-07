<?php
session_start();
include 'setup.php';

$message = [];

if (!isset($_SESSION["user_id"])) {
    $_SESSION["user_id"] = 1;
    $_SESSION["name"] = "Test Driver";
}

$loggedIn = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

if (isset($_POST['Create_a_ride'])) {

    $pickup_location = mysqli_real_escape_string($conn, $_POST['pickup_location']);
    $pickup_lat = mysqli_real_escape_string($conn, $_POST['pickup_lat']);
    $pickup_lng = mysqli_real_escape_string($conn, $_POST['pickup_lng']);

    $dropoff_location = mysqli_real_escape_string($conn, $_POST['dropoff_location']);
    $dropoff_lat = mysqli_real_escape_string($conn, $_POST['dropoff_lat']);
    $dropoff_lng = mysqli_real_escape_string($conn, $_POST['dropoff_lng']);

    $ride_date = mysqli_real_escape_string($conn, $_POST['ride_date']);
    $ride_time = mysqli_real_escape_string($conn, $_POST['ride_time']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);

    $seat_query = mysqli_query($conn, "SELECT seats_available FROM drivers WHERE user_id = '$user_id'");
    $driver = mysqli_fetch_assoc($seat_query);

    if ($driver) {
        $seats_available = $driver['seats_available'];
    } else {
        $seats_available = "";
    }

    if (
        empty($pickup_location) ||
        empty($pickup_lat) ||
        empty($pickup_lng) ||
        empty($dropoff_location) ||
        empty($dropoff_lat) ||
        empty($dropoff_lng) ||
        empty($ride_date) ||
        empty($ride_time) ||
        empty($price)
    ) {
        $message[] = 'It is really important to fill out all fields! Please do this VERY carefully.';
    } elseif (empty($seats_available)) {
        $message[] = 'I could not find your registered car seats. Please double check your driver registration.';
    } else {

        $insert = "INSERT INTO rides 
        (driver_id, pickup_location, pickup_lat, pickup_lng, dropoff_location, dropoff_lat, dropoff_lng, ride_date, ride_time, seats_available, price)
        VALUES 
        ('$user_id', '$pickup_location', '$pickup_lat', '$pickup_lng', '$dropoff_location', '$dropoff_lat', '$dropoff_lng', '$ride_date', '$ride_time', '$seats_available', '$price')";

        $upload = mysqli_query($conn, $insert);

        if ($upload) {
            $message[] = 'You have created a new ride successfully!';
        } else {
            $message[] = 'Unfortunately, the ride could not be added.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content ="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="offeraride.css">
    <title>Offer A Ride</title>
</head>

<body class="Create-A-Ride"> 
   
<nav class="nav">
    <a href="Driverdashboard.php">Dashboard</a>
</nav>

<div class="page-center">
    <div class="card">

        <?php
        if (!empty($message)) {
            foreach ($message as $message) {
                echo "<div class ='message'>$message</div>";
            }
        }
        ?>

        <form action="offeraride.php" method="post" onsubmit="return geocodeBeforeSubmit(event)">

            <h2>Offer a New Ride</h2>
            <h3>Fill in the form to create a new ride</h3>

            <label>Pickup Location:</label>
            <input type="text" placeholder="Pickup Location" name="pickup_location" id="pickup_location" class="box" required>
            <input type="hidden" name="pickup_lat" id="pickup_lat">
            <input type="hidden" name="pickup_lng" id="pickup_lng">

            <label>Dropoff Location:</label>
            <input type="text" placeholder="Dropoff Location" name="dropoff_location" id="dropoff_location" class="box" required>
            <input type="hidden" name="dropoff_lat" id="dropoff_lat">
            <input type="hidden" name="dropoff_lng" id="dropoff_lng">

            <label>Departure Date & Time:</label>
            <input type="date" name="ride_date" class="box" required>
            <input type="time" name="ride_time" class="box" required>

            <label>Price:</label>
            <input type="number" placeholder="Price per Seat £" name="price" step="0.01" min="0" class="box" required>

            <input type="submit" name="Create_a_ride" value="Post Ride">
        </form>

    </div>  
</div> 

<script>

async function geocodeLocation(location) {

    const url =
        "https://nominatim.openstreetmap.org/search?format=json&limit=1&q="
        + encodeURIComponent(location);

    const response = await fetch(url);

    const data = await response.json();

    if (data.length === 0) {
        return null;
    }

    return {
        lat: data[0].lat,
        lng: data[0].lon
    };
}

async function geocodeBeforeSubmit(event) {

    event.preventDefault();

    const pickup =
        document.getElementById("pickup_location").value;

    const dropoff =
        document.getElementById("dropoff_location").value;

    const pickupCoords =
        await geocodeLocation(pickup);

    const dropoffCoords =
        await geocodeLocation(dropoff);

    if (!pickupCoords) {
        alert("Pickup location not found. Try being more specific.");
        return false;
    }

    if (!dropoffCoords) {
        alert("Dropoff location not found. Try being more specific.");
        return false;
    }

    document.getElementById("pickup_lat").value =
        pickupCoords.lat;

    document.getElementById("pickup_lng").value =
        pickupCoords.lng;

    document.getElementById("dropoff_lat").value =
        dropoffCoords.lat;

    document.getElementById("dropoff_lng").value =
        dropoffCoords.lng;

    event.target.submit();
}

</script>

</body> 
</html>
