<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Centralized DB & Session Setup
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Auth Redirect BEFORE Any HTML Output
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();

// 3. Obtain & Sanitize Movie ID
if (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
} else {
    $movie_id = 1001; 
}

// 4. Fetch Available Dates for the Movie
$sql_date = "
    SELECT DISTINCT date 
    FROM showtime 
    WHERE movie_id = :movie_id 
      AND (LOWER(showtime_status) = 'open for booking' OR LOWER(showtime_status) = 'scheduled') 
    ORDER BY date ASC
";
$stmt_date = $pdo->prepare($sql_date);
$stmt_date->execute([':movie_id' => $movie_id]);
$dates = $stmt_date->fetchAll(PDO::FETCH_COLUMN);

// Selected date fallback
$selected_date = $_POST['date'] ?? ($dates[0] ?? '');

// 5. Fetch Movie Details
$sql_movie = "
    SELECT movie_name, path 
    FROM movie 
    WHERE movie_id = :movie_id 
    LIMIT 1
";
$stmt_movie = $pdo->prepare($sql_movie);
$stmt_movie->execute([':movie_id' => $movie_id]);
$movie_info = $stmt_movie->fetch(PDO::FETCH_ASSOC);

$movie_name = $movie_info['movie_name'] ?? '';
$movie_path = $movie_info['path'] ?? '';

// 6. Include Header (HTML Output Begins Here)
require_once __DIR__ . '/header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>CineWave-Booking Showtime</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body class="body">
    <h1>Showtime</h1>
    <section class="container">
        <div class="container">
            <img src="<?php echo htmlspecialchars($movie_path, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($movie_name, ENT_QUOTES, 'UTF-8'); ?>">
            <div>
                <h3><?php echo htmlspecialchars($movie_name, ENT_QUOTES, 'UTF-8'); ?></h3>
                
                <h4>Date:</h4>
                <?php if (!empty($dates)): ?>
                    <form method="POST" action="?id=<?php echo $movie_id; ?>">
                        <select name="date" onchange="this.form.submit()">
                            <?php foreach ($dates as $dateVal): ?>
                                <?php 
                                    $dateObj = DateTime::createFromFormat('Y-m-d', $dateVal);
                                    $displayDate = $dateObj ? $dateObj->format('D, M j, Y') : $dateVal;
                                ?>
                                <option value="<?php echo htmlspecialchars($dateVal, ENT_QUOTES, 'UTF-8'); ?>" <?php if ($selected_date === $dateVal) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($displayDate, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php else: ?>
                    <p class="text-muted">No showtimes available for this movie.</p>
                <?php endif; ?>

                <h4>Start_time:</h4>
                <?php
                if (!empty($selected_date)) {
                    $sql_times = "
                        SELECT showtime_id, start_time 
                        FROM showtime 
                        WHERE date = :selected_date 
                          AND movie_id = :movie_id
                          AND (LOWER(showtime_status) = 'open for booking' OR LOWER(showtime_status) = 'scheduled')
                        ORDER BY start_time ASC
                    ";
                    $stmt_times = $pdo->prepare($sql_times);
                    $stmt_times->execute([
                        ':selected_date' => $selected_date,
                        ':movie_id'      => $movie_id
                    ]);

                    $showtimes = $stmt_times->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($showtimes)) {
                        foreach ($showtimes as $st) {
                            $formatted_time = date("g:i A", strtotime($st['start_time']));
                            echo '<a href="seat.php?id=' . intval($st['showtime_id']) . '" class="btn">' . htmlspecialchars($formatted_time, ENT_QUOTES, 'UTF-8') . '</a> ';
                        }
                    } else {
                        echo '<p>No showtimes available for this date.</p>';
                    }
                }
                ?>
            </div>   
        </div>
    </section>

<?php include "footer.html"; ?>
</body>
</html>