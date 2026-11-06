<?php 
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
session_start();
if (isset($_GET['id'])) {
    $showtime_id = intval($_GET['id']);
} else {
    $showtime_id = 1001; 
}

$_SESSION['showtime_id'] = $showtime_id;

// Checked on booked seats
$sql_seat = "SELECT seat_id FROM ticket WHERE showtime_id = 1001";
$booked_seats_result = $pdo->query($sql_seat);
$booked_seats = [];
if ($booked_seats_result && $booked_seats_result->rowCount() > 0) {
    while ($row = $booked_seats_result->fetch(PDO::FETCH_ASSOC)) {
        $booked_seats[] = (int) $row['seat_id'];
    }
}

// Checked on available seats
$sql = "SELECT seat.seat_id, seat.seat_number, seat.seat_status, showtime.start_time, showtime.date, hall.hall_row
        FROM seat 
        LEFT JOIN hall ON hall.hall_id = seat.hall_id
        LEFT JOIN showtime ON showtime.hall_id = hall.hall_id
        WHERE showtime_id = $showtime_id";

$result = $pdo->query($sql);

$available_seats = [];
if ($result && $result->rowCount() > 0) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $available_seats[] = $row;
    }
}

$hall_row = (!empty($available_seats) && intval($available_seats[0]['hall_row']) > 0) ? intval($available_seats[0]['hall_row']) : 5;
$date = (!empty($available_seats)) ? $available_seats[0]['date'] : "N/A";
$start_time = (!empty($available_seats)) ? $available_seats[0]['start_time'] : "N/A";

include "header.php";
?>

<!DOCTYPE html>
<html>
    <head>
        <title>CineWave - Booking Seat</title>
        <link rel="stylesheet" href="css/styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    </head>
    <body class="body">
        <h1>Showtime - Seat Booking</h1>
        
        <section class="container">
            <div class="container">
                <h4>Date:</h4>
                <p><?php echo htmlspecialchars($date); ?></p>
                <h4>Start Time:</h4>
                <p><?php echo htmlspecialchars($start_time); ?></p>
                <section>
                    <div class="btn">
                        <h5>Available</h5>
                    </div>
                    <div class="btn1">
                        <h5>Unavailable</h5>
                    </div>
                </section>
            </div>
     
            <h4>Seat Layout</h4>
            <div class="container-seat">
                <?php if (!empty($available_seats)) { ?>
                    <?php 
                    $count = 0;
                    foreach ($available_seats as $seat) { 
                        // Start a new line after completing a row of seats
                        if ($count > 0 && $count % $hall_row == 0) {
                            echo "<br><br>";
                        }
                        
                        $seat_id = (int)$seat['seat_id'];
                        $is_booked = in_array($seat_id, $booked_seats, true);
                        
                        // Render booked seat
                        if ($is_booked) { ?>
                            <span class="btn1">
                                <?php echo htmlspecialchars($seat['seat_number']); ?>
                            </span>
                        <?php 
                        // Render available seat
                        } else if ($seat['seat_status'] == 'available' || $seat['seat_status'] == '0') { ?>
                            <a href="ticket.php?id=<?php echo $seat['seat_id']; ?>" class="btn">
                                <?php echo htmlspecialchars($seat['seat_number']); ?>
                            </a>
                        <?php 
                        // Render disabled seat
                        } else { ?>
                            <span class="btn1">
                                <?php echo htmlspecialchars($seat['seat_number']); ?>
                            </span>
                        <?php } 

                        $count++; 
                    } 
                    ?>
                <?php } else { ?>
                    <p>No available seats left for this showtime.</p>
                <?php } ?>
            </div>
        </section>
    </body>
    <?php include "footer.html"; ?>
</html>