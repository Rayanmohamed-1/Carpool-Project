<?php
session_start();
include 'setup.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "passenger") {
    header("Location: home.php");
    exit();
}

$passenger_id = (int) $_SESSION["user_id"];
$message = [];
$matches = [];
$seats_needed = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $pickup_location = mysqli_real_escape_string($conn, $_POST["pickup_location"]);
    $pickup_lat = (float) $_POST["pickup_lat"];
    $pickup_lng = (float) $_POST["pickup_lng"];

    $dropoff_location = mysqli_real_escape_string($conn, $_POST["dropoff_location"]);
    $dropoff_lat = (float) $_POST["dropoff_lat"];
    $dropoff_lng = (float) $_POST["dropoff_lng"];

    $ride_date = mysqli_real_escape_string($conn, $_POST["ride_date"]);
    $ride_time = mysqli_real_escape_string($conn, $_POST["ride_time"]);
    $seats_needed = (int) $_POST["seats_needed"];

    if (
        empty($pickup_location) ||
        empty($pickup_lat) ||
        empty($pickup_lng) ||
        empty($dropoff_location) ||
        empty($dropoff_lat) ||
        empty($dropoff_lng) ||
        empty($ride_date) ||
        empty($ride_time) ||
        empty($seats_needed)
    ) {
        $message[] = "Please fill in all search fields.";
    } else {

        $sql = "
            SELECT 
                r.*,
                u.full_name AS driver_name,
                u.email AS driver_email,
                d.phone,
                d.car_make_model,
                d.car_registration,
                d.car_colour,

                (
                    6371 * ACOS(
                        COS(RADIANS($pickup_lat)) *
                        COS(RADIANS(r.pickup_lat)) *
                        COS(RADIANS(r.pickup_lng) - RADIANS($pickup_lng)) +
                        SIN(RADIANS($pickup_lat)) *
                        SIN(RADIANS(r.pickup_lat))
                    )
                ) AS pickup_distance,

                (
                    6371 * ACOS(
                        COS(RADIANS($dropoff_lat)) *
                        COS(RADIANS(r.dropoff_lat)) *
                        COS(RADIANS(r.dropoff_lng) - RADIANS($dropoff_lng)) +
                        SIN(RADIANS($dropoff_lat)) *
                        SIN(RADIANS(r.dropoff_lat))
                    )
                ) AS dropoff_distance,

                ABS(TIME_TO_SEC(TIMEDIFF(r.ride_time, '$ride_time')) / 60) AS time_difference,

                (
                    (
                        6371 * ACOS(
                            COS(RADIANS($pickup_lat)) *
                            COS(RADIANS(r.pickup_lat)) *
                            COS(RADIANS(r.pickup_lng) - RADIANS($pickup_lng)) +
                            SIN(RADIANS($pickup_lat)) *
                            SIN(RADIANS(r.pickup_lat))
                        )
                    ) * 0.5
                    +
                    (
                        6371 * ACOS(
                            COS(RADIANS($dropoff_lat)) *
                            COS(RADIANS(r.dropoff_lat)) *
                            COS(RADIANS(r.dropoff_lng) - RADIANS($dropoff_lng)) +
                            SIN(RADIANS($dropoff_lat)) *
                            SIN(RADIANS(r.dropoff_lat))
                        )
                    ) * 0.3
                    +
                    ABS(TIME_TO_SEC(TIMEDIFF(r.ride_time, '$ride_time')) / 60) * 0.2
                ) AS match_score

            FROM rides r
            JOIN users u ON r.driver_id = u.id
            LEFT JOIN drivers d ON d.user_id = u.id

            WHERE 
                r.ride_date = '$ride_date'
                AND r.seats_available >= $seats_needed
                AND r.driver_id != $passenger_id

            HAVING 
                pickup_distance <= 3
                AND dropoff_distance <= 3
                AND time_difference <= 60

            ORDER BY match_score ASC
        ";

        $result = mysqli_query($conn, $sql);

        if (!$result) {
            die("MYSQL ERROR: " . mysqli_error($conn));
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $matches[] = $row;
        }

        if (empty($matches)) {
            $message[] = "No matching rides found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Matching Rides</title>
    <link rel="stylesheet" href="matching.css">
</head>

<body class="driver-dashboard">

<header class="Header">
    <h1 class="brand">Carpool</h1>
    <nav class="nav">
        <a href="passengerdashboard.php">Dashboard</a>
        <a href="logout.php" class="logout">Logout</a>
    </nav>
</header>

<div class="page-center">
    <div class="card">

        <p class="DD">Ride Matching</p>
        <h1 class="welcome">Matching Rides</h1>
        <p class="dashboard-text">Here are the best available rides based on pickup distance, dropoff distance and time.</p>

        <?php foreach ($message as $msg): ?>
            <div class="message">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($matches)): ?>

            <div class="table-wrapper">
                <table class="matches-table">
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Car</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Seats</th>
                            <th>Price</th>
                            <th>Pickup Distance</th>
                            <th>Dropoff Distance</th>
                            <th>Time Difference</th>
                            <th>Book</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($matches as $ride): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ride["driver_name"]); ?></td>

                                <td>
                                    <?php echo htmlspecialchars($ride["car_colour"] . " " . $ride["car_make_model"]); ?><br>
                                    <small><?php echo htmlspecialchars($ride["car_registration"]); ?></small>
                                </td>

                                <td><?php echo htmlspecialchars($ride["pickup_location"]); ?></td>
                                <td><?php echo htmlspecialchars($ride["dropoff_location"]); ?></td>
                                <td><?php echo htmlspecialchars($ride["ride_date"]); ?></td>
                                <td><?php echo htmlspecialchars($ride["ride_time"]); ?></td>
                                <td><?php echo htmlspecialchars($ride["seats_available"]); ?></td>
                                <td>£<?php echo htmlspecialchars($ride["price"]); ?></td>
                                <td><?php echo round($ride["pickup_distance"], 2); ?> km</td>
                                <td><?php echo round($ride["dropoff_distance"], 2); ?> km</td>
                                <td><?php echo round($ride["time_difference"]); ?> mins</td>

                                <td>
                                    <form action="bookride.php" method="post">
                                        <input type="hidden" name="ride_id" value="<?php echo $ride["id"]; ?>">
                                        <input type="hidden" name="seats_booked" value="<?php echo $seats_needed; ?>">
                                        <input type="submit" value="Book Ride" class="small-btn">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>
</div>

</body>
</html>