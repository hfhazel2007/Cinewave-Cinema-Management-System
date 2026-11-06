
<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
if (isset($_GET['id'])) {
    $showtime_id = intval($_GET['id']);
} else {
    $showtime_id = 1001;
}

if ($showtime_id > 0) {
    $pdo->query("SET FOREIGN_KEY_CHECKS = 0");


$sql = "DELETE FROM showtime WHERE showtime_id = $showtime_id";
if ($pdo->query($sql) === TRUE) {
        header("Location:showtime_management.php");
        exit();
    } }
?>
<!DOCTYPE html>
<html>

<head>
    <title>Cinewave-Showtime delete</title>
</head>

<body>
    <h2>Delete Showtime</h2>
    <?php
        echo "Error: " . $pdo->$error;

    ?>

</body>

</html>