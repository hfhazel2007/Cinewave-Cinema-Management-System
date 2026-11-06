<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

$sql= "SELECT * FROM promotion";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Cinewave-Promotion Management</title>
        <link rel="stylesheet" href="css/styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Promotion Management</h1>
        <table border="1" class="table" >
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Poster</th>
                <th>Discount Percentage</th>
                <th>Condition</th>
            </tr>
            <?php  while($row=$result->fetch(PDO::FETCH_ASSOC)){?>
            <tr>
                <td><?php echo $row['promotion_id'];?></td>
                <td><?php echo $row['promotion_name'];?></td>
                <td><img src="<?php echo $row['path'];?>" alt="<?php echo $row['promotion_name'];?>" width="250px"></td>
                <td><?php echo $row['discount_percentage'];?></td>
                <td><?php echo $row['conditions'];?></td>
                <td>
                    <a href="promotion_edit.php?id=<?php echo $row['promotion_id'];?>"class="btn">Edit</a>
                    <a href="promotion_delete.php?id=<?php echo $row['promotion_id'];?>"class="btn">Delete</a>
                </td>
            </tr>
            <?php
            }?>
        </table>
        <a href="add_promotion.php" class="btn">Add Promotion</a>
    </body>
</html>





