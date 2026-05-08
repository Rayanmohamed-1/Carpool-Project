<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "driver") {
    header("Location: home.php");
    exit();
}

$name = $_SESSION["user_name"] ?? "Driver";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>

    <link rel="stylesheet" href="DriverDashboard.css">

    <link 
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >
</head>

<body class="driver-dashboard">

<header class="Header">

    <h1 class="brand">Driver Zone</h1>

    <nav class="nav">
        <a href="profile.php">Profile</a>
        <a href="dlogout.php" class="logout">Logout</a>
    </nav>

</header>

<main class="page-center">

    <section class="card">

        <p class="DD">Driver Dashboard</p>

        <h2 class="welcome">
            Welcome <?= htmlspecialchars($name) ?>
        </h2>

        <p class="dashboard-text">
            Create new rides, view upcoming rides, manage passenger bookings and check your profile.
        </p>

        <div class="action-grid">

            <a href="offeraride.php" class="dashboard-btn-offer-ride">
                <i class="fa-solid fa-car"></i>
                <span>Offer a Ride</span>
            </a>

            <a href="UpcomingRides.php" class="dashboard-btn-Upcoming-Rides">
                <i class="fa-regular fa-calendar"></i>
                <span>Upcoming Rides</span>
            </a>

            <a href="dbooking.php" class="dashboard-btn-bookings">
                <i class="fa-regular fa-clock"></i>
                <span>Bookings</span>
            </a>

        </div>

    </section>

</main>

</body>
</html>