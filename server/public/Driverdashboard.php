<?php 
session_start();
if (!isset($_SESSION["user_id"])) {
    $_SESSION["user_id"] = 1;
    $_SESSION["name"] = "Test Driver";
}

$loggedIn = isset($_SESSION["user_id"]);
$name = $_SESSION["name"] ?? "Driver";
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="DriverDashboard.css">
    <title>Driver Dashboard</title>
</head>
<body class="driver-dashboard">
    <header class="Header">
        <h1 class="brand">Driver Zone</h1>

        <nav class="nav">
            <a href="profile.php">Profile</a>
            <a href="logoutdriver.php" class="logout">Logout</a>
        </nav>
    
    </header>
    <main class="page-center">
        <section class="card">
            <p class="DD">Driver Dashboard</p>
            <h2 class="welcome">
                Welcome
            </h2>
            <p class="dashboard-text">
                Create new rides, view upcoming rides as well as modifying them and check out your profile! 
            </p>
            <div class="action-grid">
                <a href="offeraride.php" class="dashboard-btn-offer-ride"></a>
                <a href="UpcomingRides.php" class="dashboard-btn-Upcoming-Rides"></a>
            </div>
        </section>
    </main>
</body>
</html>

