<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
$sql= "SELECT hall.hall_id,hall.hall_number,hall.capacity,hall.hall_status,cinema.address FROM hall LEFT JOIN cinema on hall.cinema_id=cinema.cinema_id ";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Cinewave-Hall Management</title>
        <link rel="stylesheet" href="css/styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Hall Management</h1>
        <table border="1" class="table" >
            <tr>
                <th>ID</th>
                <th>Number</th>
                <th>Capacity</th>
                <th>Hall Status</th>
                <th>Branch</th>
            </tr>
            <?php  while($row=$result->fetch(PDO::FETCH_ASSOC)){?>
            <tr>
                <td><?php echo $row['hall_id'];?></td>
                <td><?php echo $row['hall_number'];?></td>
                <td><?php echo $row['capacity'];?></td>
                <td><?php echo $row['hall_status'];?></td>
                <td><?php echo $row['address'];?></td>
                <td>
                    <a href="hall_edit.php?id=<?php echo $row['hall_id'];?>" class="btn">Edit</a>
                    <a href="hall_delete.php?id=<?php echo $row['hall_id'];?>"class="btn">Delete</a>
                </td>
            </tr>
            <?php
            }?>
        </table>
        <a href="add_hall.php" class="btn">Add Hall</a>
    </body>
</html>
