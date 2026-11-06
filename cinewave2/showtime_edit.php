<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
if (isset($_GET['id'])) {
    $showtime_id = intval($_GET['id']);
} else {
    $showtime_id = 1; 
}

?>            
<?php
if(isset($_POST['update'])){
        $date=$_POST['date'];
        $start_time=$_POST['start_time'];
        $showtime_status=$_POST['showtime_status'];   
        $hall_number=   $_POST['hall_number'];    
        $movie_name=$_POST['movie_name'];
        $sql_movie="SELECT movie_id FROM movie WHERE movie_name='$movie_name'";
        $result_movie = $pdo->query($sql_movie);
        $row_movie=$result_movie->fetch(PDO::FETCH_ASSOC);
        $movie_id=$row_movie['movie_id'];
        $sql_hall="SELECT hall_id FROM hall WHERE hall_number='$hall_number'";
        $result_hall= $pdo->query($sql_hall);
        $row_hall=$result_hall->fetch(PDO::FETCH_ASSOC);
        $hall_id=$row_hall['hall_id'];
        $sql="UPDATE showtime SET date='$date',start_time='$start_time',showtime_status='$showtime_status', hall_id='$hall_id',movie_id='$movie_id' where showtime_id=$showtime_id";
        if($pdo->query($sql) === TRUE){
            header("Location: showtime_management.php");    
            exit();
        }else {
            echo "Error: " . $pdo->$error;
    }
    }
$sql= "SELECT * FROM showtime WHERE showtime_id = $showtime_id";
$result = $pdo->query($sql);
$row=$result->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
    <head>
        <title>CineWave-Showtime Edit</title>
    </head>
<body>
<h1>Showtime Edit</h1> 
    
 <form action="showtime_edit.php?id=<?php echo $showtime_id; ?>" method="post">
    Date:<br>
    <input type="date" name="date" value="<?php echo $row['date']; ?>" required>
    <br><br>

    Start Time:<br>
    <input type="time" name="start_time" value="<?php echo $row['start_time']; ?>" required>
     <br><br>

    Showtime Status:<br>
    <select name="showtime_status" required>
    <option value="Open for Booking">Open for Booking</option>
    <option value="Scheduled">Scheduled</option>
    <option value="Sold Out">Sold Out</option>
    <option value="Cancelled">Cancelled</option>
    </select>
    <br><br>

    Movie:<br>
    <select name="movie_name" required>
    <?php
        $sql_movie="SELECT movie_name FROM movie WHERE current_showing_status='Now Showing'";
        $result_movie = $pdo->query($sql_movie);
        while($row_movie=$result_movie->fetch(PDO::FETCH_ASSOC)){
    ?>
    <option value="<?php echo$row_movie['movie_name']?>"><?php echo$row_movie['movie_name']?></option>
    <?php
    }?>
    </select>
<br><br>
    Hall:<br>
    <select name="hall_number" required>
    <?php
        $sql_hall="SELECT hall_number FROM hall WHERE hall_status='Available'";
        $result_hall = $pdo->query($sql_hall);
        while($row_hall=$result_hall->fetch(PDO::FETCH_ASSOC)){
    ?>
    <option value="<?php echo$row_hall['hall_number']?>"><?php echo$row_hall['hall_number']?></option>
    <?php
    }?>
    </select>
    <br><br>
    <input type="submit" name="update" value="Update">
</form>

</body>
</html>
             