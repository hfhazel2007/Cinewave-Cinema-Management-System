<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

$movie = "SELECT movie_id, movie_name FROM movie";
$movieResult = $pdo->query($movie);

$showtime = "SELECT showtime_id, movie_id, date, start_time FROM showtime ORDER BY date, start_time";
$showtimeResult = $pdo->query($showtime);
?>

<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Group Booking</title>

    <link rel="stylesheet" href="css/styles.css">

    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>

<section class="booking-banner">
    <h1>Group Booking</h1>
    <p>Enjoy exclusive movie experiences for schools, companies and organisations.</p>
</section>

<section class="booking-container">

<form action="" method="post">

    <label>Organisation / Group Name</label>
    <input type="text" name="organisation_name" required>

    <label>Contact Person</label>
    <input type="text" name="contact_person" required>

    <label>Contact Details</label>
    <input type="text" name="contact_details" placeholder="Phone or Email" required>

    <label>Movie</label>
    <select name="movie_id" required>

        <option value="">Select Movie</option>

        <?php
        while($row = $movieResult->fetch(PDO::FETCH_ASSOC))
        {
        ?>
            <option value="<?php echo $row['movie_id']; ?>">
                <?php echo $row['movie_name']; ?>
            </option>
        <?php
        }
        ?>

    </select>

    <label>Preferred Showtime</label>

    <select name="showtime_id" required>

        <option value="">Select Showtime</option>

        <?php
        while($row = $showtimeResult->fetch(PDO::FETCH_ASSOC))
        {
        ?>
            <option value="<?php echo $row['showtime_id']; ?>">
                <?php echo $row['date']; ?> |
                <?php echo date("g:i A", strtotime($row['start_time'])); ?>
            </option>
        <?php
        }
        ?>

    </select>

    <label>Number of Attendees</label>
    <input type="number" name="attendees" min="10" required>

    <label>Seating Requirements</label>
    <textarea name="seating_requirements" rows="4"></textarea>

    <label>F&B Requirements</label>
    <textarea name="fb_requirements" rows="4"></textarea>

    <input type="submit" value="Submit Booking" class="booking-btn">

</form>

<div class="rules">

    <h2>Business Rules</h2>

    <ul>
        <li>Advance booking required.</li>
        <li>Deposit/payment deadline may apply.</li>
        <li>Bulk F&amp;B ordering supported.</li>
    </ul>

</div>

</section>

</body>

<?php
include "footer.html";
?>

</html>