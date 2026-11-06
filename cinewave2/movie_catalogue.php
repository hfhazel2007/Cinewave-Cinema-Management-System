<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM movie WHERE current_showing_status = 'Now Showing'");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>CineWave-Movie</title>
        <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Movie</h1>
        <div class="container">
        <h3>Now Showing</h3>
       </div>
        <section class="container">
            <?php
            foreach ($result as $row) {
                if ($row['age_restriction'] === 'PG-18' && $_SESSION['role'] === 'children') {
                    continue;
                }else{
            ?>
            <div>
                <img src="<?php echo$row['path']?>" alt="<?php echo$row['movie_name']?>">
                <br>
                <a href="movie_booking.php?id=<?php echo $row['movie_id'];?>" class="btn" >Book Now</a>
            </div>
            <?php
            }}
            ?>
        </section>
        <div class="container">
            <h3>Coming Soon</h3>
        </div>
        <section class="container">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM movie WHERE current_showing_status = 'Coming Soon'");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
        ?>
        <div>
            <img src="<?php echo htmlspecialchars($row['path']); ?>" alt="<?php echo htmlspecialchars($row['movie_name']); ?>">
            <br>
            <a href="movie_booking.php?id=<?php echo $row['movie_id'];?>"  class="btn" >More Info</a>
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