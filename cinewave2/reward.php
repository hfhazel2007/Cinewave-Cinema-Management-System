<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$sql = "SELECT * FROM reward";
$result = $pdo->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>CineWave-Reward</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body class="body">
    <h1>Reward</h1>
    <section class="container">
    <?php
    // Use fetch() instead of fetchAll() inside the while loop to get one row at a time
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    ?>
    
    <div class="card-reward">
        <h3>
            <?php echo htmlspecialchars($row["reward_name"]); ?>
        </h3>
        <p class="points">
            <?php echo htmlspecialchars($row["points_required"]); ?> pts
        </p>
        <p class="expiry">
            <?php echo htmlspecialchars($row["exp_date"]); ?>
        </p>
    </div>

    <?php
    }
    ?>
    </section>
</body>
<?php
if (file_exists("footer.html")) {
    include "footer.html";
}
?>
</html>