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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pickup_location = mysqli_real_escape_string($conn, $_POST['pickup_location']);
    $pickup_lat = mysqli_real_escape_string($conn, $_POST['pickup_lat']);
    $pickup_lng = mysqli_real_escape_string($conn, $_POST['pickup_lng']);

    $dropoff_location = mysqli_real_escape_string($conn, $_POST['dropoff_location']);
    $dropoff_lat = mysqli_real_escape_string($conn, $_POST['dropoff_lat']);
    $dropoff_lng = mysqli_real_escape_string($conn, $_POST['dropoff_lng']);

    $ride_date = mysqli_real_escape_string($conn, $_POST['ride_date']);
    $ride_time = mysqli_real_escape_string($conn, $_POST['ride_time']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);

    $driver_query = mysqli_query($conn, "SELECT id, seats_available FROM drivers WHERE user_id = $user_id");
    $driver = mysqli_fetch_assoc($driver_query);

    $driver_id = $driver['id'] ?? '';
    $seats_available = $driver['seats_available'] ?? '';

    if (
        empty($pickup_location) ||
        empty($pickup_lat) ||
        empty($pickup_lng) ||
        empty($dropoff_location) ||
        empty($dropoff_lat) ||
        empty($dropoff_lng) ||
        empty($ride_date) ||
        empty($ride_time) ||
        $price === ''
    ) {
        $message[] = 'Please fill out all fields carefully.';
    } elseif (empty($driver_id) || empty($seats_available)) {
        $message[] = 'I could not find your registered car seats. Please update your driver profile.';
    } else {

        $insert = "INSERT INTO rides 
        (driver_id, pickup_location, pickup_lat, pickup_lng, dropoff_location, dropoff_lat, dropoff_lng, ride_date, ride_time, seats_available, price)
        VALUES 
        ($driver_id, '$pickup_location', '$pickup_lat', '$pickup_lng', '$dropoff_location', '$dropoff_lat', '$dropoff_lng', '$ride_date', '$ride_time', '$seats_available', '$price')";

        $upload = mysqli_query($conn, $insert);

        if (!$upload) {
            die("MYSQL ERROR: " . mysqli_error($conn));
        } else {
            header("Location: Driverdashboard.php");
            exit();
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
        foreach ($message as $msg) {
            echo "<div class='message'>" . htmlspecialchars($msg) . "</div>";
        }
        ?>

        <form action="offeraride.php" method="post" onsubmit="return geocodeBeforeSubmit(event)">
            <h2>Offer a New Ride</h2>
            <h3>Fill in the form to create a new ride</h3>

            <label>Pickup Location:</label>
            <div class="autocomplete-wrapper">
                <input type="text" placeholder="Pickup Location" name="pickup_location" id="pickup_location" class="box" autocomplete="off" required>
                <div id="pickup_results" class="autocomplete-results"></div>
            </div>
            <input type="hidden" name="pickup_lat" id="pickup_lat">
            <input type="hidden" name="pickup_lng" id="pickup_lng">

            <label>Dropoff Location:</label>
            <div class="autocomplete-wrapper">
                <input type="text" placeholder="Dropoff Location" name="dropoff_location" id="dropoff_location" class="box" autocomplete="off" required>
                <div id="dropoff_results" class="autocomplete-results"></div>
            </div>
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
async function searchOpenStreetMap(location, limit = 5) {
    const url =
        "https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&countrycodes=gb&limit="
        + limit
        + "&q="
        + encodeURIComponent(location);

    const response = await fetch(url);
    return await response.json();
}

async function geocodeLocation(location) {
    const data = await searchOpenStreetMap(location, 1);

    if (data.length === 0) {
        return null;
    }

    return {
        lat: data[0].lat,
        lng: data[0].lon,
        address: data[0].display_name
    };
}

function setupAutocomplete(inputId, resultsId) {
    const input = document.getElementById(inputId);
    const resultsBox = document.getElementById(resultsId);
    let timer;

    input.addEventListener("input", function () {
        clearTimeout(timer);

        timer = setTimeout(async function () {
            const location = input.value.trim();
            resultsBox.innerHTML = "";

            if (location.length < 2) {
                resultsBox.style.display = "none";
                return;
            }

            const results = await searchOpenStreetMap(location, 5);

            if (results.length === 0) {
                resultsBox.style.display = "none";
                return;
            }

            results.forEach(function (place) {
                const item = document.createElement("div");
                item.className = "autocomplete-item";
                item.textContent = place.display_name;

                item.onclick = function () {
                    input.value = place.display_name;
                    resultsBox.innerHTML = "";
                    resultsBox.style.display = "none";
                };

                resultsBox.appendChild(item);
            });

            resultsBox.style.display = "block";

        }, 300);
    });

    document.addEventListener("click", function (event) {
        if (!input.contains(event.target) && !resultsBox.contains(event.target)) {
            resultsBox.innerHTML = "";
            resultsBox.style.display = "none";
        }
    });
}

async function geocodeBeforeSubmit(event) {
    event.preventDefault();

    const pickupInput = document.getElementById("pickup_location");
    const dropoffInput = document.getElementById("dropoff_location");

    const pickupCoords = await geocodeLocation(pickupInput.value.trim());
    const dropoffCoords = await geocodeLocation(dropoffInput.value.trim());

    if (!pickupCoords) {
        alert("Pickup location not found. Try being more specific.");
        return false;
    }

    if (!dropoffCoords) {
        alert("Dropoff location not found. Try being more specific.");
        return false;
    }

    pickupInput.value = pickupCoords.address;
    dropoffInput.value = dropoffCoords.address;

    document.getElementById("pickup_lat").value = pickupCoords.lat;
    document.getElementById("pickup_lng").value = pickupCoords.lng;

    document.getElementById("dropoff_lat").value = dropoffCoords.lat;
    document.getElementById("dropoff_lng").value = dropoffCoords.lng;

    event.target.submit();
}

setupAutocomplete("pickup_location", "pickup_results");
setupAutocomplete("dropoff_location", "dropoff_results");
</script>

</body> 
</html>