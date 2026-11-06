<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Cinewave_info_page</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body class="body">
    <h1><a href="index.php">Home</a></h1>
    <section class="container">
        <div class="card">
            <a href="booking_management.php">Booking Management</a>
        </div>
        <div class="card">
            <a href="add_movie.php">Add Movie</a>
        </div>
        <div class="card">
            <a href="edit_movie.php">Edit Movie</a>
        </div>
        <div class="card">
            <a href="fnbmanagement.php">FNB Management</a>
        </div>
        <div class="card">
            <a href="showtime_management.php">Showtime Management</a>
        </div>
        <div class="card">
            <a href="hall_management.php">Hall Management</a>
        </div>
        <div class="card">
            <a href="promotion_management.php">Promotion Management</a>
        </div>
        <div class="card">
            <a href="reward_management.php">Reward Management</a>
        </div>
        <div class="card">
            <a href="report.php">Report generator</a>
        </div>
    </section>
</body>

</html>