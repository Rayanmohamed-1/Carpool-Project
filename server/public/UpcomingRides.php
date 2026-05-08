<?php
session_start();
include 'setup.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "driver") {
    header("Location: home.php");
    exit();
}

$message = [];
$user_id = (int) $_SESSION['user_id'];
$edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : null;

$driver_query = mysqli_query($conn, "SELECT id FROM drivers WHERE user_id = $user_id");
$driver = mysqli_fetch_assoc($driver_query);
$driver_id = $driver['id'] ?? 0;

if (isset($_SESSION['success_message'])) {
    $message[] = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];

    mysqli_query(
        $conn,
        "DELETE FROM rides 
         WHERE id = $delete_id 
         AND driver_id = $driver_id"
    );

    header('Location: UpcomingRides.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['edit_id'] ?? 0);

    if ($id === 0) {
        $message[] = 'Please select the ride you want to edit.';
    } else {
        $pickup_location = mysqli_real_escape_string($conn, $_POST['pickup_location']);
        $pickup_lat = mysqli_real_escape_string($conn, $_POST['pickup_lat']);
        $pickup_lng = mysqli_real_escape_string($conn, $_POST['pickup_lng']);
        $dropoff_location = mysqli_real_escape_string($conn, $_POST['dropoff_location']);
        $dropoff_lat = mysqli_real_escape_string($conn, $_POST['dropoff_lat']);
        $dropoff_lng = mysqli_real_escape_string($conn, $_POST['dropoff_lng']);
        $ride_date = mysqli_real_escape_string($conn, $_POST['ride_date']);
        $ride_time = mysqli_real_escape_string($conn, $_POST['ride_time']);
        $seats_available = (int) $_POST['seats_available'];
        $price = mysqli_real_escape_string($conn, $_POST['price']);

        if (
            empty($pickup_location) ||
            empty($pickup_lat) ||
            empty($pickup_lng) ||
            empty($dropoff_location) ||
            empty($dropoff_lat) ||
            empty($dropoff_lng) ||
            empty($ride_date) ||
            empty($ride_time) ||
            empty($seats_available) ||
            $price === ''
        ) {
            $message[] = 'Please fill out all fields.';
        } else {
            $update_sql = "
                UPDATE rides 
                SET
                    pickup_location='$pickup_location',
                    pickup_lat='$pickup_lat',
                    pickup_lng='$pickup_lng',
                    dropoff_location='$dropoff_location',
                    dropoff_lat='$dropoff_lat',
                    dropoff_lng='$dropoff_lng',
                    ride_date='$ride_date',
                    ride_time='$ride_time',
                    seats_available='$seats_available',
                    price='$price'
                WHERE id=$id
                AND driver_id=$driver_id
            ";

            $update = mysqli_query($conn, $update_sql);

            if ($update) {
                $message[] = 'The ride has been updated successfully.';
            } else {
                $message[] = 'Ride could not be updated: ' . mysqli_error($conn);
            }
        }
    }
}

$select = mysqli_query(
    $conn,
    "
    SELECT * FROM rides
    WHERE driver_id = $driver_id
    ORDER BY ride_date ASC, ride_time ASC
    "
);

$edit_row = null;

if ($edit_id) {
    $result = mysqli_query(
        $conn,
        "
        SELECT * FROM rides
        WHERE id = $edit_id
        AND driver_id = $driver_id
        "
    );

    $edit_row = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Rides</title>
    <link rel="stylesheet" href="UpcomingRidess.css">
</head>

<body class="Create-A-Ride">

<nav class="nav">
    <a href="Driverdashboard.php">Dashboard</a>
</nav>

<div class="page-center">

    <?php
    foreach ($message as $msg) {
        echo '<span class="message">' . htmlspecialchars($msg) . '</span>';
    }
    ?>

    <div class="Card">
        <h1>Upcoming Rides</h1>
        <h2>Select a ride to view, edit or delete</h2>

        <?php if ($edit_id && $edit_row): ?>
            <form action="UpcomingRides.php" method="post" onsubmit="return geocodeBeforeSubmit(event)">
                <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">

                <div class="form-group">
                    <label>Pickup Location:</label>

                    <div class="autocomplete-wrapper">
                        <input
                            type="text"
                            name="pickup_location"
                            id="pickup_location"
                            value="<?php echo htmlspecialchars($edit_row['pickup_location'] ?? ''); ?>"
                            autocomplete="off"
                            required
                        >

                        <div id="pickup_results" class="autocomplete-results"></div>
                    </div>
                </div>

                <input type="hidden" name="pickup_lat" id="pickup_lat" value="<?php echo htmlspecialchars($edit_row['pickup_lat'] ?? ''); ?>">
                <input type="hidden" name="pickup_lng" id="pickup_lng" value="<?php echo htmlspecialchars($edit_row['pickup_lng'] ?? ''); ?>">

                <div class="form-group">
                    <label>Dropoff Location:</label>

                    <div class="autocomplete-wrapper">
                        <input
                            type="text"
                            name="dropoff_location"
                            id="dropoff_location"
                            value="<?php echo htmlspecialchars($edit_row['dropoff_location'] ?? ''); ?>"
                            autocomplete="off"
                            required
                        >

                        <div id="dropoff_results" class="autocomplete-results"></div>
                    </div>
                </div>

                <input type="hidden" name="dropoff_lat" id="dropoff_lat" value="<?php echo htmlspecialchars($edit_row['dropoff_lat'] ?? ''); ?>">
                <input type="hidden" name="dropoff_lng" id="dropoff_lng" value="<?php echo htmlspecialchars($edit_row['dropoff_lng'] ?? ''); ?>">

                <div class="form-group">
                    <label>Ride Date:</label>
                    <input type="date" name="ride_date" value="<?php echo htmlspecialchars($edit_row['ride_date'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Ride Time:</label>
                    <input type="time" name="ride_time" value="<?php echo htmlspecialchars($edit_row['ride_time'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Available Seats:</label>
                    <input type="number" name="seats_available" value="<?php echo htmlspecialchars($edit_row['seats_available'] ?? ''); ?>" min="1" max="6" required>
                </div>

                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" name="price" value="<?php echo htmlspecialchars($edit_row['price'] ?? ''); ?>" step="0.01" min="0" required>
                </div>

                <input type="submit" value="Update Ride">
            </form>
        <?php endif; ?>
    </div>

    <div class="Upcoming-Rides-display">
        <table class="rUpcoming-Rides-display-table">
            <thead>
                <tr>
                    <th>Pickup Location</th>
                    <th>Dropoff Location</th>
                    <th>Ride Date</th>
                    <th>Ride Time</th>
                    <th>Available Seats</th>
                    <th>Price</th>
                    <th>Edit or Delete</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($select && mysqli_num_rows($select) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($select)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['pickup_location']); ?></td>
                            <td><?php echo htmlspecialchars($row['dropoff_location']); ?></td>
                            <td><?php echo htmlspecialchars($row['ride_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['ride_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['seats_available']); ?></td>
                            <td>£<?php echo htmlspecialchars($row['price']); ?></td>
                            <td>
                                <a href="UpcomingRides.php?edit=<?php echo $row['id']; ?>" class="btn">edit</a>
                                <a href="UpcomingRides.php?delete=<?php echo $row['id']; ?>" class="btn delete-btn" onclick="return confirm('Delete this ride?');">delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No upcoming rides found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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

    if (!data.length) return null;

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

        timer = setTimeout(async () => {
            const location = input.value.trim();
            resultsBox.innerHTML = "";

            if (location.length < 2) {
                resultsBox.classList.remove("show");
                return;
            }

            const results = await searchOpenStreetMap(location, 5);

            if (!results.length) {
                resultsBox.classList.remove("show");
                return;
            }

            results.forEach(place => {
                const item = document.createElement("div");

                item.className = "autocomplete-item";
                item.textContent = place.display_name;

                item.addEventListener("click", () => {
                    input.value = place.display_name;

                    if (inputId === "pickup_location") {
                        document.getElementById("pickup_lat").value = place.lat;
                        document.getElementById("pickup_lng").value = place.lon;
                    }

                    if (inputId === "dropoff_location") {
                        document.getElementById("dropoff_lat").value = place.lat;
                        document.getElementById("dropoff_lng").value = place.lon;
                    }

                    resultsBox.classList.remove("show");
                });

                resultsBox.appendChild(item);
            });

            resultsBox.classList.add("show");
        }, 300);
    });

    document.addEventListener("click", function (e) {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.remove("show");
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
        alert("Pickup location not found.");
        return false;
    }

    if (!dropoffCoords) {
        alert("Dropoff location not found.");
        return false;
    }

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