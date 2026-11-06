<?php 
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
if (isset($_POST['submit_ticket'])) {
    $_SESSION['ticket_type'] = $_POST['ticket_type'];
    if ($_SESSION['ticket_type']=="Adult"){
        $_SESSION['ticket_price']='20.00';
    } 
    if ($_SESSION['ticket_type']=="Child"||$_SESSION['ticket_type']=="Senior"){
        $_SESSION['ticket_price']='10.00';
    }
    if ($_SESSION['ticket_type']=="Student"){
        $_SESSION['ticket_price']='15.00';
    }
    $_SESSION['seat_id'] = intval($_POST['seat_id']);
}
if (isset($_GET['id'])) {
    $seat_id = intval($_GET['id']);
} else {
    $seat_id = 1001; 
}


if (isset($_SESSION['showtime_id'])) {
    $showtime_id = intval($_SESSION['showtime_id']);
} else {
    $showtime_id = 1001; 
}


$sql_showtime = "SELECT date, start_time FROM showtime WHERE showtime_id = $showtime_id";
$result_showtime = $pdo->query($sql_showtime);
$row_showtime = $result_showtime->fetch(PDO::FETCH_ASSOC);

$sql_seat = "SELECT seat_number FROM seat WHERE seat_id = $seat_id";
$result_seat = $pdo->query($sql_seat);
$row_seat = $result_seat->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>CineWave - Booking Ticket</title>
        <link rel="stylesheet" href="css/styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Confirm Ticket Selection</h1>
        <section class="container">
            <div class="container">
               
                
                
               <form action="fnb_order.php" method="POST">
                    <input type="hidden" name="seat_id" value="<?php echo $seat_id; ?>">
                    <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">

                    <h4>Date:</h4>
                    <p><?php echo htmlspecialchars($row_showtime['date'] ?? 'N/A'); ?></p>
                    
                    <h4>Start Time:</h4>
                    <p><?php echo htmlspecialchars(date("g:i A", strtotime($row_showtime['start_time']))); ?></p>
                    
                    <h4>Seat Number:</h4>
                    <p >
                        <?php echo htmlspecialchars($row_seat['seat_number'] ?? 'N/A'); ?>
                    </p>

                    <h4>Ticket Type:</h4>
                    <select name="ticket_type" id="ticket_type" required >
                        <option value="Adult">Adult </option>
                        <option value="Adult">Child </option>
                        <option value="Adult">Student</option>
                        <option value="Senior">Senior</option>
                    </select>
                        <a href="fnb_order.php?id=<?php echo htmlspecialchars($seat_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn">Next</a>
                </form>
                   
            </div>
        </section>
    </body>
    <?php include "footer.html"; ?>
</html>
