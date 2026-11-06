<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
?>
<?php
//monthly report
$monthlySalesStmt = "SELECT DATE_FORMAT(payment_datetime,'%Y-%m') AS month,SUM(amount_paid) AS total_sales,COUNT(payment_id) AS tx_count
                    FROM payment WHERE payment_status = 'Paid'
                    GROUP BY month ORDER BY month DESC";
$monthlySalesResult = $pdo->query($monthlySalesStmt);

//yearly report
$yearlySalesStmt = "SELECT YEAR(payment_datetime) AS year,SUM(amount_paid) AS total_sales,
    COUNT(payment_id) AS tx_count
    FROM payment WHERE payment_status = 'Paid'
    GROUP BY year ORDER BY year ASC";

$yearlySalesResult = $pdo->query($yearlySalesStmt);

//movie ticket seperate sales
$ticketQuery = "SELECT t.ticket_type,COUNT(t.ticket_id) AS sold,
    SUM(t.ticket_price) AS rev FROM payment p
    JOIN total_order o ON p.total_order_id = o.total_order_id
    JOIN ticket t ON o.ticket_id = t.ticket_id
    WHERE p.payment_status = 'Paid'
    GROUP BY t.ticket_type ORDER BY sold DESC";

$ticketResult = $pdo->query($ticketQuery);

//seperate single fnb sales
$seperateFnbQuery = "SELECT f2.fnb_name,SUM(f1.quantity) AS sold,
    SUM(f1.fnb_total_price) AS rev FROM payment p
    JOIN total_order o ON p.total_order_id = o.total_order_id
    JOIN fnb_item f1 ON o.fnb_item_id=f1.fnb_item_id
    JOIN fnb f2 ON f1.fnb_id = f2.fnb_id
    WHERE p.payment_status = 'Paid'
    GROUP BY f2.fnb_id,f2.fnb_name ORDER BY sold DESC";

$seperateFnbQueryResult = $pdo->query($seperateFnbQuery);

//movie rating
$movieDashQuery = "SELECT m.movie_name,COUNT(t.ticket_id) AS sold,SUM(t.ticket_price) AS rev FROM payment p
    JOIN total_order o ON p.total_order_id = o.total_order_id
    JOIN ticket t ON o.ticket_id=t.ticket_id
    JOIN showtime s ON t.showtime_id = s.showtime_id
    JOIN movie m ON s.movie_id = m.movie_id
    WHERE p.payment_status = 'Paid'
    GROUP BY m.movie_id ORDER BY sold DESC";

$movieQueryResult = $pdo->query($movieDashQuery);

include "header.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Cinewave_info_page</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  
</head>

<body class="body">
    <h1>Cinewave Annual and Analytical Report</h1>
    <div class="container">
        <div class="report_container">
            <div class="card">
                <h2>Sale Monthly</h2>
                <table class="report_table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>quantity</th>
                            <th>amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $monthlySalesResult->fetchAll(PDO::FETCH_ASSOC));
 { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['month']) ?></td>
                                <td><?= $row['tx_count'] ?></td>
                                <td><?= number_format($row['total_sales'], 2) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Annual Sales</h2>
                <table class="report_table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>quantity order</th>
                            <th>amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $yearlySalesResult->fetchAll(PDO::FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['year']) ?></td>
                                <td><?= $row['tx_count'] ?></td>
                                <td><?= number_format($row['total_sales'], 2) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Dashboard Popular Movie</h2>
                <table class="report_table">
                    <thead>
                        <tr>
                            <th>Movie Name</th>
                            <th>no. ticket sell</th>
                            <th>amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $movieQueryResult->fetchAll(PDO::FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['movie_name']) ?></td>
                                <td><?= $row['sold'] ?></td>
                                <td><?= number_format($row['rev'], 2) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>TicketSales</h2>
                <table class="report_table">
                    <thead>
                        <tr>
                            <th>Type Ticket</th>
                            <th>Quantity Sold</th>
                            <th>amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        while ($row = $ticketResult->fetchAll(PDO::FETCH_ASSOC)){
  ?>
                            <tr>
                                <td><?= htmlspecialchars($row['ticket_type']) ?></td>
                                <td><?= $row['sold'] ?></td>
                                <td><?= number_format($row['rev'], 2) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Food&Beverage Sales</h2>
                <table class="report_table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity Sold</th>
                            <th>Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        while ($row = $seperateFnbQueryResult->fetchAll(PDO::FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fnb_name']) ?></td>
                                <td><?= $row['sold'] ?></td>
                                <td><?= number_format($row['rev'], 2) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>
<?php
include "footer.php";
?>