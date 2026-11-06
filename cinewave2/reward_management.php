<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
$sql= "SELECT *FROM reward ";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Cinewave-Reward Management</title>
        <link rel="stylesheet" href="css/styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Reward Management</h1>
        <table border="1" class="table" >
            <tr>
                <th>ID</th>
                <th>Reward Name</th>
                <th>Points Required</th>
                <th>Expiry date</th>
            </tr>
            <?php  while($row=$result->fetch(PDO::FETCH_ASSOC)){?>
            <tr>
                <td><?php echo $row['reward_id'];?></td>
                <td><?php echo $row['reward_name'];?></td>
                <td><?php echo $row['points_required'];?></td>
                <td><?php echo $row['exp_date'];?></td>
                <td>
                    <a href="reward_edit.php?id=<?php echo $row['reward_id'];?>" class="btn">Edit</a>
                    <a href="reward_delete.php?id=<?php echo $row['reward_id'];?>"class="btn">Delete</a>
                </td>
            </tr>
            <?php
            }?>
        </table>
        <a href="add_reward.php" class="btn">Add Promotion</a>
    </body>
</html>