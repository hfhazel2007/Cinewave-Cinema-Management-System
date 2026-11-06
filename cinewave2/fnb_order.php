<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Session & DB Setup BEFORE Any HTML Output
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

if (!isset($_SESSION['cart'])) {$_SESSION['cart'] = [];
}

// 2. Resolve Seat & Showtime Context Across GET and SESSION
$seat_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) 
    ?? $_SESSION['seat_id'] 
    ?? 0;

$showtime_id = filter_input(INPUT_GET, 'showtime_id', FILTER_VALIDATE_INT) 
    ?? $_SESSION['showtime_id'] 
    ?? 1001;

if ($seat_id > 0) {
    $_SESSION['seat_id'] =$seat_id;
}
if ($showtime_id > 0) {
    $_SESSION['showtime_id'] =$showtime_id;
}

$has_seat_id = ($seat_id > 0);

// Helper function to maintain GET context across tab links
function getUrlWithParams($tab,$extra_param = '') {
    global $has_seat_id,$seat_id;
    $url = "fnb_order.php?tab=" . urlencode($tab);
    if ($has_seat_id) {
        $url .= "&id=" . intval($seat_id);
    }
    if (!empty($extra_param)) {
        $url .= "&" . $extra_param;
    }
    return $url;
}

// -----------------------------------------------------------------------------
// POST / GET ACTION HANDLERS (Must execute BEFORE header.php output)
// -----------------------------------------------------------------------------

// Action A: Remove Item from Cart
if (isset($_GET['remove_key'])) {
    $remove_key = intval($_GET['remove_key']);
    if (isset($_SESSION['cart'][$remove_key])) {
        unset($_SESSION['cart'][$remove_key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
    }
    $active_tab = (isset($_GET['tab']) && in_array($_GET['tab'], ['fnb', 'combo'])) ?$_GET['tab'] : 'fnb';
    header("Location: " . getUrlWithParams($active_tab, "open_receipt=1"));
    exit();
}

// Action B: Process FNB Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fnb_order'])) {$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?? 1;
    $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?? 0.0;
    $fnb_id   = filter_input(INPUT_POST, 'fnb_id', FILTER_VALIDATE_INT);
    $remarks  = trim($_POST['remarks'] ?? '');

    if ($fnb_id &&$quantity > 0) {
        $stmt_name =$pdo->prepare("SELECT fnb_name FROM fnb WHERE fnb_id = :fnb_id LIMIT 1");
        $stmt_name->execute([':fnb_id' =>$fnb_id]);
        $row =$stmt_name->fetch(PDO::FETCH_ASSOC);

        if ($row) {$_SESSION['cart'][] = [
                'type'       => 'FNB',
                'item_id'    => $fnb_id,
                'name'       => $row['fnb_name'],
                'quantity'   => $quantity,
                'unit_price' => $price,
                'total'      => $quantity * $price,
                'remarks'    => $remarks
            ];
        }
    }

    header("Location: " . getUrlWithParams('fnb', 'success=1'));
    exit();
}

// Action C: Process Combo Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['combo_order'])) {$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?? 1;
    $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?? 0.0;
    $combo_id = filter_input(INPUT_POST, 'combo_id', FILTER_VALIDATE_INT);
    $remarks  = trim($_POST['remarks'] ?? '');

    if ($combo_id &&$quantity > 0) {
        $stmt_name =$pdo->prepare("SELECT combo_name FROM combo WHERE combo_id = :combo_id LIMIT 1");
        $stmt_name->execute([':combo_id' =>$combo_id]);
        $row =$stmt_name->fetch(PDO::FETCH_ASSOC);

        if ($row) {$_SESSION['cart'][] = [
                'type'       => 'Combo',
                'item_id'    => $combo_id,
                'name'       => $row['combo_name'],
                'quantity'   => $quantity,
                'unit_price' => $price,
                'total'      => $quantity * $price,
                'remarks'    => $remarks
            ];
        }
    }

    header("Location: " . getUrlWithParams('combo', 'success=1'));
    exit();
}

$valid_tabs = ['fnb', 'combo'];$active_tab = (isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs)) ?$_GET['tab'] : 'fnb';

// 3. Include Header (HTML Output Starts Here)
require_once __DIR__ . '/header.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>CineWave - FNB</title>
        <link rel="stylesheet" href="css/styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
        <style>
            .alert-success {
                background-color: #1b4332;
                color: #2dc653;
                border: 1px solid #2dc653;
                padding: 12px;
                border-radius: 6px;
                text-align: center;
                max-width: 600px;
                margin: 15px auto;
                font-weight: bold;
                transition: opacity 0.5s ease;
            }
            .modal-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                background: rgba(0, 0, 0, 0.85);
                z-index: 1000;
                justify-content: center;
                align-items: center;
            }
            .modal-card {
                background: #1e1e2f;
                color: #ffffff;
                padding: 25px;
                border-radius: 12px;
                max-width: 450px;
                width: 90%;
                border: 1px solid #333;
            }
            .modal-card input[type="number"],
            .modal-card input[type="text"] {
                width: 100%; padding: 10px;
                margin: 8px 0 16px 0;
                border-radius: 6px; border: 1px solid #444;
                background-color: #2a2a40; color: #fff;
                box-sizing: border-box;
            }
            .receipt-card {
                background: #141423; color: #e0e0e0;
                padding: 25px; border-radius: 12px;
                max-width: 600px; width: 90%;
                border: 1px solid #e84118;
            }
            .receipt-table {
                width: 100%; border-collapse: collapse; margin: 15px 0;
            }
            .receipt-table th, .receipt-table td {
                padding: 10px; border-bottom: 1px solid #2c2c44; text-align: left;
            }
            .receipt-table th { background-color: #27273e; color: #f5f6fa; }
            .btn-drop {
                background-color: #ff4757; color: #ffffff;
                border: none; padding: 5px 10px; border-radius: 4px;
                text-decoration: none; font-size: 0.85em;
            }
            .modal-actions { display: flex; justify-content: space-between; margin-top: 20px; gap: 10px; }
            .btn-cancel {
                background-color: #57606f; color: #ffffff;
                padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;
            }
        </style>
    </head>
    <body class="body">
        <h1>FNB</h1>
        
        <div class="auth-tabs">
            <a href="<?php echo getUrlWithParams('combo'); ?>" class="<?php echo ($active_tab === 'combo') ? 'active' : ''; ?>">Combo</a>
            <a href="<?php echo getUrlWithParams('fnb'); ?>" class="<?php echo ($active_tab === 'fnb') ? 'active' : ''; ?>">Fnb</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div id="successAlert" class="alert-success">Item added successfully!</div>
        <?php endif; ?>

        <?php if (!$has_seat_id): ?>
            <div style="text-align: center; color: #ff6b6b; margin-bottom: 15px;">
                <p>Please select a ticket/seat first to place food orders.</p>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'fnb'): ?>
            <section class="container">
                <?php 
                $stmt =$pdo->query("SELECT * FROM fnb ORDER BY fnb_id ASC");
                if ($stmt) {
                    while ($row =$stmt->fetch(PDO::FETCH_ASSOC)) { 
                        $fnb_id_val    =$row['fnb_id'];
                        $fnb_name_val  =$row['fnb_name'];
                        $fnb_price_val =$row['fnb_price'];
                        $path_val      =$row['path'] ?? 'asset/fnb/default.jpg';
                        $desc_val      =$row['description'] ?? '';
                ?>
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($path_val, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($fnb_name_val, ENT_QUOTES, 'UTF-8'); ?>" height="200">
                        <h3><?php echo htmlspecialchars($fnb_name_val, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars($desc_val, ENT_QUOTES, 'UTF-8'); ?></p>
                        <h5>RM <?php echo number_format($fnb_price_val, 2); ?></h5>
                        
                        <?php if (isset($_SESSION['id']) &&$has_seat_id): ?>
                            <button type="button" class="btn" style="display:block; width:100%; text-align:center;" 
                                    onclick="openOrderModal('fnb', '<?php echo $fnb_id_val; ?>', '<?php echo htmlspecialchars($fnb_name_val, ENT_QUOTES); ?>', '<?php echo$fnb_price_val; ?>')">
                                Add
                            </button>
                        <?php endif; ?>
                    </div>
                <?php 
                    }
                } 
                ?>
            </section>

        <?php else: ?>
            <section class="container">
                <?php 
                $stmt =$pdo->query("SELECT * FROM combo ORDER BY combo_id ASC");
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {$combo_id_val    = $row['combo_id'];$combo_name_
