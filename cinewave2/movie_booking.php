<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

if (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
} else {
    $movie_id = 1001; 
}
$stmt = $pdo->prepare("SELECT * FROM movie WHERE movie_id = ?");
$stmt->execute([$movie_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>CineWave-Movie-Booking</title>
        <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Movie</h1>
        <section class="container">
            <div>
            <img src="<?php echo$row['path']?>" alt="<?php echo$row['movie_name']?>">
            </div>
            <div >
            <h3><?php echo$row['movie_name']?></h3>
            <h4>Synopsis:</h4>
            <p><?php echo$row['synopsis']?></p>
            <h4>Genre:</h4>
            <p><?php echo$row['genre']?></p>
            <h4>Language:</h4>
            <p><?php echo$row['language']?></p>
            <h4>Subtitle:</h4>
            <p><?php echo$row['subtitle']?></p>
            <h4>Age Restrcition:</h4>
            <p><?php echo$row['age_restriction']?></p>
            <?php
            if($row['current_showing_status']=='Now Showing'){
                ?>
            <a href="showtime_selection.php?movie_id=<?php echo $row['movie_id']; ?>" class="btn" >Buy Ticket</a>
            <?php } ?>
            </div>
            
</section>

</body>
<?php
include "footer.html";
?>
</html>