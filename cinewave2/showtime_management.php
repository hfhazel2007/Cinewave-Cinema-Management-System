<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

$sql= "SELECT showtime.showtime_id, showtime.start_time,showtime.date,showtime.showtime_status,movie.movie_name,hall.hall_number FROM showtime LEFT JOIN movie on showtime.movie_id=movie.movie_id LEFT JOIN hall ON showtime.hall_id=hall.hall_id";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Cinewave-Showtime Management</title>
        <link rel="stylesheet" href="css/styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Showtime Management</h1>
        <table border="1" class="table">
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>Status</th>
                <th>Movie Name</th>
                <th>Hall Number</th>
</tr>
            <?php  while($row=$result->fetch(PDO::FETCH_ASSOC)){?>
            <tr>
                <td><?php echo $row['showtime_id'];?></td>
                <td><?php echo $row['date'];?></td>
                <td><?php echo $row['start_time'];?></td>
                <td><?php echo $row['showtime_status'];?></td>
                <td><?php echo $row['movie_name'];?></td>
                <td><?php echo $row['hall_number'];?></td>
                <td>
                    <a href="showtime_edit.php?id=<?php echo $row['showtime_id'];?>" class="btn">Edit</a>
                    <a href="showtime_delete.php?id=<?php echo $row['showtime_id'];?>"class="btn">Delete</a>
                </td>
            </tr>
            <?php
            }?>
        </table>
        <a href="add_showtime.php" class="btn">Add Showtime</a>
    </body>
</html>
