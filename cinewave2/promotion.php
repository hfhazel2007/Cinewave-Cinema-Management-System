<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
$sql="SELECT *FROM promotion";
$result=$pdo->query($sql);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>CineWave-Promotion</title>
       <link rel="stylesheet" href="css/styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Promotion</h1>
        <section class="container">
        <?php
        while($row=$result -> fetch(PDO::FETCH_ASSOC)){
        ?>
        <div class="card">
            <img src="<?php echo $row["path"];?>" alt="<?php echo $row["promotion_name"]?>"/>
            <h3><?php echo $row["promotion_name"]?></h3>  
            <h5>Conditions</h5>
             <p><?php echo $row["conditions"]?></p>
        </div>
        <?php
        }
        ?>
        </section>
    </body>
<?php
include "footer.html";
?>
</html>