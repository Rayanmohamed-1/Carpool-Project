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

$driver_id = (int) $_SESSION["user_id"];

$sql = "
    SELECT 
        rb.id AS booking_id,
        rb.seats_booked,
        rb.status,
        rb.created_at AS booking_date,
        r.pickup_location,
        r.dropoff_location,
        r.ride_date,
        r.ride_time,
        r.price,
        u.full_name AS passenger_name,
        u.email AS passenger_email,
        u.faculty,
        u.student_id
    FROM ride_bookings rb
    JOIN rides r ON rb.ride_id = r.id
    JOIN users u ON rb.passenger_id = u.id
    WHERE r.driver_id = $driver_id
    ORDER BY r.ride_date ASC, r.ride_time ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("MYSQL ERROR: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Bookings</title>
    <link rel="stylesheet" href="dbooking.css">
</head>

<body class="driver-dashboard">

<header class="Header">
    <h1 class="brand">Who Booked You?</h1>

    <nav class="nav">
        <a href="Driverdashboard.php">Dashboard</a>
        <a href="dlogout.php" class="logout">Logout</a>
    </nav>
</header>

<div class="page-center">
    <div class="card">
        <p class="DD">Driver Area</p>
        <h1 class="welcome">Passenger Bookings</h1>
        <p class="dashboard-text">These are the passengers who booked your rides.</p>

        <div class="table-wrapper">
            <table class="matches-table">
                <thead>
                    <tr>
                        <th>Passenger</th>
                        <th>Email</th>
                        <th>Faculty</th>
                        <th>Student ID</th>
                        <th>Pickup</th>
                        <th>Dropoff</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Seats</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["passenger_name"]); ?></td>
                                <td><?php echo htmlspecialchars($row["passenger_email"]); ?></td>
                                <td><?php echo htmlspecialchars($row["faculty"] ?? "N/A"); ?></td>
                                <td><?php echo htmlspecialchars($row["student_id"] ?? "N/A"); ?></td>
                                <td><?php echo htmlspecialchars($row["pickup_location"]); ?></td>
                                <td><?php echo htmlspecialchars($row["dropoff_location"]); ?></td>
                                <td><?php echo htmlspecialchars($row["ride_date"]); ?></td>
                                <td><?php echo htmlspecialchars($row["ride_time"]); ?></td>
                                <td><?php echo htmlspecialchars($row["seats_booked"]); ?></td>
                                <td><?php echo htmlspecialchars($row["status"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">No passengers have booked your rides yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>