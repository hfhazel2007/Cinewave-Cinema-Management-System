<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
if (isset($_GET['id'])) {
    $hall_id = intval($_GET['id']);
} else {
    $hall_id = 1; 
}

$sql = "DELETE FROM hall WHERE hall_id = $hall_id";
?>
<!DOCTYPE html>
<html>

<head>
    <title>Cinewave- Hall delete</title>
</head>

<body>
    <h2>Delete Hall</h2>
    <?php
    if ($pdo->query($sql) === TRUE) {
        header("Location:hall_management.php");
    } else {
        echo "Error: " . $pdo->$error;
    }
    ?>

</body>
