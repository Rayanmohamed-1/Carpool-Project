<<<<<<< HEAD
<?php
header("Location: home.html");
exit;
?>
=======
<!DOCTYPE html>
<html lang ="en">
    
<head>
    <meta charset ="utf-8"/>
    <meta name="viewport" content="width=device-width, intial-scale=1"/>
    <title> Main Dashboard</title>

    <link rel="stylesheet" href="style.css"/>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
</head>

<body class="Create-A-Ride">

<div class="Create-A-Ride">
    <div class="card">

    <h2>Passenger Dashbaord</h2>
    <h3>Find a Ride</h3>

    <form method="Get">
        <label>From</label>
        <input type="text" name="from" placeholder="Enter location">

        <label>To</label>
        <input tyep="text" name="to" placeholder="Enter destination">

        <input type="submit" value="Search">
    </form>

    <div style="margin-top: 30px;">
        <h4>Avaible Rides</h4>

        <?php
        $rides =[
            ["from" => "Cardiff", "to" => "Newport", "price" => 5],
            ["from" => "Cardiff", "to" => "Swansea", "price" => 7],
            ["from" => "Cardiff", "to" => "Bristol", "price" => 10]
        ];

        foreach ($rides as $ride) {
            echo "<div style='border:1px solid #ccc; padding:10px; margin-top:10px 0;'>
            {$ride['from']} -> {$ride['to']} <br>
            £{$ride['price']}
            </div>";
        }
        ?>
    </div>

    <div id="map" style="height:300px; margin-top:20px;"></div>
    </div>
</div>

<script>
    function iniMap() {
        const cardiff ={lat: 51.4816, lng: -3.1791};

        const map = new google.maps.Map(document.getElementById("map"), {
            zoom: 12,
            center: cardiff,
        });

        new google.maps.Marker({
            position: cardiff,
            map: map,
        });
    }
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>

</body>
</html>


>>>>>>> 00ae2d4607f1adce5cf774210c75f5fb304df9d1
