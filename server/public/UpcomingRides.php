<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    $_SESSION["user_id"] = 1;
    $_SESSION["name"] = "Test Driver";
}

include 'setup.php';

$message = [];
$user_id = (int) $_SESSION['user_id'];
$edit_id = $_GET['edit'] ?? null;

if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];

    mysqli_query($conn, "DELETE FROM rides WHERE id = $delete_id AND driver_id = $user_id");

    header('Location: UpcomingRides.php');
    exit();
}
if(isset($_POST['Update_a_Ride'])){ 
    $id = $_POST['edit_id'] ?? 0;
    
    if($id == 0){
        $message[] = 'please select the ride you want to edit.';
}else{
$pickup_location  = $_POST['pickup_location'];
$dropoff_location  = $_POST['dropoff_location'];
$ride_date  = $_POST['ride_date'];
$ride_time  = $_POST['ride_time'];
$seats_available = $_POST['seats_available'];
$price  = $_POST['price'];
        
if(empty($pickup_location) ||empty($dropoff_location) ||empty($ride_date)|| empty($ride_time)||empty($seats_available) || empty($price)){
    $message[] = 'Please fill out all fields.';
 }
else{
    $update = "UPDATE rides 
    SET pickup_location='$pickup_location', dropoff_location='$dropoff_location', ride_date ='$ride_date', ride_time ='$ride_time',seats_available='$seats_available', price ='$price' WHERE id=$id AND driver_id=$user_id";        

  $update = mysqli_query($conn, $update);

  if($update){
      $message[] = 'The ride has been updated successfully.';
  } else{ 
      $message[] = 'Unfortunately, the ride could not be updated.';
  }   
}
}
}

$select =  mysqli_query($conn, "SELECT * FROM rides  WHERE driver_id=$user_id ORDER BY ride_time ASC");
$edit_row = null;
if($edit_id){
    $result = mysqli_query($conn, "SELECT * FROM rides WHERE id=$edit_id AND driver_id=$user_id");
    $edit_row = mysqli_fetch_assoc($result);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="UpcomingRidess.css">
    <title>Upcoming Rides</title>
</head>
<body class="Create-A-Ride">
<nav class="nav">
    <a href="Driverdashboard.php">Dashboard</a>
</nav>
<?php 
if (!empty($message)) {
        foreach ($message as $message) {
            echo '<span class="message">'.$message.'</span>';
        }
    }
 ?>
    <div class="page-center"> 
    <div class="Card">
        <h1> Upcoming Rides</h1>
        <h2>Select A Ride to view, edit or even delete!</h2>
        <?php if($edit_id && $edit_row) : ?>
        <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
            <input type= "hidden" name="edit_id" value="<?php echo $edit_id; ?>">
            <label>Pickup Location:</label>
            <input type="text" placeholder="Pickup Location" name="pickup_location" value="<?php echo $edit_row['pickup_location'] ??''; ?>" class="box" required>
            <input type="hidden" name="pickup_lat" id="pickup_lat">
            <input type="hidden" name="pickup_lng" id="pickup_lng">
            <label>Dropoff Location:</label>
            <input type="text" placeholder="Dropoff Location" name="dropoff_location" value="<?php echo $edit_row['dropoff_location'] ??''; ?>"  class="box" required>
            <input type="hidden" name="dropoff_lat" id="dropoff_lat">
            <input type="hidden" name="dropoff_lng" id="dropoff_lng">
            <label>Ride Date:</label>
            <input type="date" name="ride_date" value="<?php echo $edit_row['ride_date'] ??''; ?>" class="box" required>
            <label>Ride Time:</label>
            <input type="time" name="ride_time" value="<?php echo $edit_row['ride_time'] ??''; ?>" class="box" required>
            <label>Available Seats:</label>
            <input type="number" placeholder="Available Seats" name="seats_available" value="<?php echo $edit_row['seats_available'] ??''; ?>" min="1" max="6" class="box" required>
            <label>Price:</label>
            <input type="number" placeholder="Price per Seat £" name="price" value="<?php echo $edit_row['price'] ??''; ?>" step="0.01" min="0" class="box" required>
            <input type="submit" name="Update_a_Ride" value="Update Ride">
        </form>
        <?php endif; ?>
     </div>
      <div class="Upcoming-Rides-display">
        
      <table class="rUpcoming-Rides-display-table">
            <thead>
              <tr>
                <td> Pickup Location </td>
                    <td> Dropoff Location </td>
                    <td> Ride Date </td>
                    <td> Ride Time </td>
                    <td> Available seats </td>
                    <td > Price </td>
                    <td>Edit or Delete</td>
                    
              </tr>
            </thead>

            <tbody> 
                <?php while($row = mysqli_fetch_assoc($select)): ?>
                    <tr> 
                        <td><?php echo $row['pickup_location']; ?></td>
                        <td><?php echo $row['dropoff_location']; ?></td>
                        <td><?php echo $row['ride_date']; ?></td>
                        <td><?php echo $row['ride_time']; ?></td>
                        <td><?php echo $row['seats_available']; ?></td>
                        <td><?php echo $row['price']; ?></td>
                        <td>
                        <a href="UpcomingRides.php?edit=<?php echo $row['id'];?>" class="btn"> <i class="fas fa-edit"></i> edit </a>
                        <a href="UpcomingRides.php?delete=<?php echo $row['id'];?>" class="btn delete-btn">delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div> 
</body>
</html>


