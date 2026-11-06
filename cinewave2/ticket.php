<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Session Setup & DB Connection (Must be BEFORE any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

// Auth Guard: Ensure user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();

// 2. Obtain Seat ID & Showtime ID from GET, POST, or SESSION
$seat_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) 
    ?? filter_input(INPUT_POST, 'seat_id', FILTER_VALIDATE_INT) 
    ?? $_SESSION['seat_id'] 
    ?? 1001;

$showtime_id = filter_input(INPUT_GET, 'showtime_id', FILTER_VALIDATE_INT) 
    ?? filter_input(INPUT_POST, 'showtime_id', FILTER_VALIDATE_INT) 
    ?? $_SESSION['showtime_id'] 
    ?? 1001;

// Persist context in session
$_SESSION['seat_id'] = $seat_id;
$_SESSION['showtime_id'] = $showtime_id;

// 3. Handle Form Submission (Saves Ticket & Redirects to F&B)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $ticket_type = trim($_POST['ticket_type'] ?? 'Adult');
    $_SESSION['ticket_type'] = $ticket_type;

    // Ticket pricing logic (Restricted to Adult, Senior, Student, OKU)
    switch ($ticket_type) {
        case 'Senior':
        case 'OKU':
            $_SESSION['ticket_price'] = 10.00;
            break;
        case 'Student':
            $_SESSION['ticket_price'] = 15.00;
            break;
        case 'Adult':
        default:
            $_SESSION['ticket_price'] = 20.00;
            break;
    }

    // Perform header redirect BEFORE any HTML is output to browser
    header("Location: fnb_order.php?id=" . urlencode($seat_id));
    exit();
}

// 4. Fetch Showtime Details via Prepared Statement
$sql_showtime = "SELECT date, start_time FROM showtime WHERE showtime_id = :showtime_id LIMIT 1";
$stmt_showtime = $pdo->prepare($sql_showtime);
$stmt_showtime->execute([':showtime_id' => $showtime_id]);
$row_showtime = $stmt_showtime->fetch(PDO::FETCH_ASSOC);

// 5. Fetch Seat Details via Prepared Statement
$sql_seat = "SELECT seat_number FROM seat WHERE seat_id = :seat_id LIMIT 1";
$stmt_seat = $pdo->prepare($sql_seat);
$stmt_seat->execute([':seat_id' => $seat_id]);
$row_seat = $stmt_seat->fetch(PDO::FETCH_ASSOC);

// 6. NOW Include Header (HTML Output Starts Here)
require_once __DIR__ . '/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CineWave - Booking Ticket</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body class="body">

    <h1>Confirm Ticket Selection</h1>

    <section class="container">
        <div class="container">
            
            <form action="ticket.php?id=<?php echo htmlspecialchars($seat_id, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                <input type="hidden" name="seat_id" value="<?php echo htmlspecialchars($seat_id, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="showtime_id" value="<?php echo htmlspecialchars($showtime_id, ENT_QUOTES, 'UTF-8'); ?>">

                <h4>Date:</h4>
                <p><?php echo htmlspecialchars($row_showtime['date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                
                <h4>Start Time:</h4>
                <p>
                    <?php 
                    if (!empty($row_showtime['start_time'])) {
                        echo htmlspecialchars(date("g:i A", strtotime($row_showtime['start_time'])), ENT_QUOTES, 'UTF-8');
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </p>
                
                <h4>Seat Number:</h4>
                <p><?php echo htmlspecialchars($row_seat['seat_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>

                <h4>Ticket Type:</h4>
                <select name="ticket_type" id="ticket_type" required style="padding: 8px 12px; font-size: 1rem; margin-bottom: 20px;">
                    <option value="Adult" <?php echo (($_SESSION['ticket_type'] ?? '') === 'Adult') ? 'selected' : ''; ?>>Adult (RM 20.00)</option>
                    <option value="Student" <?php echo (($_SESSION['ticket_type'] ?? '') === 'Student') ? 'selected' : ''; ?>>Student (RM 15.00)</option>
                    <option value="Senior" <?php echo (($_SESSION['ticket_type'] ?? '') === 'Senior') ? 'selected' : ''; ?>>Senior (RM 10.00)</option>
                    <option value="OKU" <?php echo (($_SESSION['ticket_type'] ?? '') === 'OKU') ? 'selected' : ''; ?>>OKU (RM 10.00)</option>
                </select>

                <br>
                <button type="submit" name="submit_ticket" class="btn">Next &rarr;</button>
            </form>
               
        </div>
    </section>

<?php include "footer.html"; ?>
</body>
</html>
