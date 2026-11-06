<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
if (isset($_GET['id'])) {
    $promotion_id = intval($_GET['id']);
} else {
    $promotion_id = 1; 
}

$sql = "DELETE FROM promotion WHERE promotion_id = $promotion_id";
?>
<!DOCTYPE html>
<html>

<head>
    <title>Cinewave-Promotion delete</title>
</head>

<body>
    <h2>Delete Promotion</h2>
    <?php
    if ($pdo->query($sql) === TRUE) {
        header("Location:promotion_management.php");
    } else {
        echo "Error: " . $pdo->$error;
    }
    ?>

</body>
