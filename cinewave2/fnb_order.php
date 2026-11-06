<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$has_seat_id = isset($_GET['id']) && !empty($_GET['id']);
$seat_id     = $has_seat_id ? intval($_GET['id']) : 0; 
$showtime_id = isset($_SESSION['showtime_id']) ? intval($_SESSION['showtime_id']) : 1001; 

// 1. Process Item Removal from Cart
if (isset($_GET['remove_key'])) {
    $remove_key = intval($_GET['remove_key']);
    if (isset($_SESSION['cart'][$remove_key])) {
        unset($_SESSION['cart'][$remove_key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    $active_tab = (isset($_GET['tab']) && in_array($_GET['tab'], ['fnb', 'combo'])) ? $_GET['tab'] : 'fnb';
    $redirect_url = "fnb_order.php?tab=" . $active_tab . ($has_seat_id ? "&id=" . $seat_id : "") . "&open_receipt=1";
    header("Location: " . $redirect_url);
    exit();
}

// 2. Process FNB Order
if (isset($_POST['fnb_order'])) {
    $quantity = intval($_POST['quantity']);
    $remarks  = isset($_POST['remarks']) ? $pdo->quote($_POST['remarks']) : '';
    $price    = floatval($_POST['price']);
    $fnb_id   = intval($_POST['fnb_id']); // Ensure this is > 0
    $fnb_total_price = $quantity * $price;
    
    // Check database to ensure valid item
    $name_res = $pdo->query("SELECT fnb_name FROM fnb WHERE fnb_id = $fnb_id");
    if ($name_res && $row = $name_res->fetch(PDO::FETCH_ASSOC)) {
        $item_name = $row['fnb_name'];

        $_SESSION['cart'][] = [
            'type'       => 'FNB',
            'item_id'    => $fnb_id, // MUST BE > 0
            'name'       => $item_name,
            'quantity'   => $quantity,
            'unit_price' => $price,
            'total'      => $fnb_total_price,
            'remarks'    => $remarks
        ];
    }

    $redirect_url = "fnb_order.php?tab=fnb&success=1" . ($has_seat_id ? "&id=" . $seat_id : "");
    header("Location: " . $redirect_url);
    exit();
}

// 3. Process Combo Order (FIXED: Captures combo_id explicitly)
if (isset($_POST['combo_order'])) {
    $quantity = intval($_POST['quantity']);
    $remarks  = isset($_POST['remarks']) ? $pdo->quote($_POST['remarks']) : '';
    $price    = floatval($_POST['price']);
    $combo_id = intval($_POST['combo_id']);
    $combo_total_price = $quantity * $price;
    
    $name_res = $pdo->query("SELECT combo_name FROM combo WHERE combo_id = $combo_id");
    $item_name = ($name_res && $row = $name_res->fetch(PDO::FETCH_ASSOC)) ? $row['combo_name'] : 'Combo Item';

    $_SESSION['cart'][] = [
        'type'       => 'Combo',
        'item_id'    => $combo_id,
        'name'       => $item_name,
        'quantity'   => $quantity,
        'unit_price' => $price,
        'total'      => $combo_total_price,
        'remarks'    => $remarks
    ];

    $redirect_url = "fnb_order.php?tab=combo&success=1" . ($has_seat_id ? "&id=" . $seat_id : "");
    header("Location: " . $redirect_url);
    exit();
}

$valid_tabs = ['fnb', 'combo'];
$active_tab = (isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs)) ? $_GET['tab'] : 'fnb';

function getUrlWithParams($tab, $extra_param = '') {
    global $has_seat_id, $seat_id;
    $url = "fnb_order.php?tab=" . $tab;
    if ($has_seat_id) {
        $url .= "&id=" . $seat_id;
    }
    if (!empty($extra_param)) {
        $url .= "&" . $extra_param;
    }
    return $url;
}
?>

<!DOCTYPE html>
<html>
    <head>
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
                $sql = "SELECT * FROM fnb";
                $result = $pdo->query($sql);
                if ($result) {
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) { 
                        $fnb_id_val    = $row['fnb_id'];
                        $fnb_name_val  = $row['fnb_name'];
                        $fnb_price_val = $row['fnb_price'];
                        $path_val      = isset($row['path']) ? $row['path'] : '';
                        $desc_val      = isset($row['description']) ? $row['description'] : '';
                ?>
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($path_val); ?>" alt="<?php echo htmlspecialchars($fnb_name_val); ?>" height="200">
                        <h3><?php echo htmlspecialchars($fnb_name_val); ?></h3>
                        <p><?php echo htmlspecialchars($desc_val); ?></p>
                        <h5>$<?php echo number_format($fnb_price_val, 2); ?></h5>
                        
                        <?php if (isset($_SESSION['id']) && $has_seat_id): ?>
                            <button type="button" class="btn" style="display:block; width:100%; text-align:center;" 
                                    onclick="openOrderModal('fnb', '<?php echo $fnb_id_val; ?>', '<?php echo htmlspecialchars($fnb_name_val, ENT_QUOTES); ?>', '<?php echo $fnb_price_val; ?>')">
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
                $sql = "SELECT * FROM combo";
                $result = $pdo->query($sql);
                if ($result) {
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) { 
                        $combo_id_val    = $row['combo_id'];
                        $combo_name_val  = $row['combo_name'];
                        $combo_price_val = $row['combo_price'];
                        $path_val        = isset($row['path']) ? $row['path'] : '';
                        $desc_val        = isset($row['description']) ? $row['description'] : '';
                ?>
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($path_val); ?>" alt="<?php echo htmlspecialchars($combo_name_val); ?>" height="200">
                        <h3><?php echo htmlspecialchars($combo_name_val); ?></h3>
                        <p><?php echo htmlspecialchars($desc_val); ?></p>
                        <h5>$<?php echo number_format($combo_price_val, 2); ?></h5>
                        
                        <?php if (isset($_SESSION['id']) && $has_seat_id): ?>
                            <button type="button" class="btn" style="display:block; width:100%; text-align:center;" 
                                    onclick="openOrderModal('combo', '<?php echo $combo_id_val; ?>', '<?php echo htmlspecialchars($combo_name_val, ENT_QUOTES); ?>', '<?php echo $combo_price_val; ?>')">
                                Add
                            </button>
                        <?php endif; ?>
                    </div>
                <?php 
                    }
                } 
                ?>
            </section>
        <?php endif; ?>
        
        <div style="text-align:center; margin-top: 20px;">
            <?php if (isset($_SESSION['id']) && $has_seat_id): ?>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <button type="button" class="btn" onclick="openReceiptModal()">Pay</button>
                <?php else: ?>
                    <a href="payment.php?id=<?php echo $seat_id; ?>" class="btn">Pay</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Order Modal -->
        <div id="orderModal" class="modal-overlay">
            <div class="modal-card">
                <h2 id="modalItemTitle">Select Item</h2>
                <form id="orderModalForm" method="POST" action="">
                    <input type="hidden" id="modalItemId" name="" value="">
                    <input type="hidden" id="modalItemPrice" name="price" value="">
                    
                    <label>Quantity</label>
                    <input type="number" name="quantity" value="1" min="1" required>
                    
                    <label>Remarks</label>
                    <input type="text" name="remarks" placeholder="Optional notes (e.g. Less ice)">
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeOrderModal()">Cancel</button>
                        <button type="submit" id="modalSubmitBtn" name="" class="btn">Confirm Order</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Receipt Modal -->
        <div id="receiptModal" class="modal-overlay">
            <div class="receipt-card">
                <h2>Order Receipt Summary</h2>
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        if (!empty($_SESSION['cart'])):
                            foreach ($_SESSION['cart'] as $index => $item): 
                                $grand_total += $item['total'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?> <small>(<?php echo htmlspecialchars($item['type']); ?>)</small></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>$<?php echo number_format($item['total'], 2); ?></td>
                                <td>
                                    <a href="<?php echo getUrlWithParams($active_tab, 'remove_key=' . $index); ?>" class="btn-drop">Drop</a>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                        else:
                        ?>
                            <tr><td colspan="5" style="text-align: center; color: #aaa;">No food or combo items selected.</td></tr>
                        <?php endif; ?>
                        <tr style="font-weight: bold; background-color: #1e1e36; color: #e84118;">
                            <td colspan="3" style="text-align:right;">Grand Total:</td>
                            <td colspan="2">$<?php echo number_format($grand_total, 2); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeReceiptModal()">Modify Order</button>
                    <a href="payment.php?id=<?php echo $seat_id; ?>" class="btn">Proceed to Payment</a>
                </div>
            </div>
        </div>

        <script>
            const alertBox = document.getElementById('successAlert');
            if (alertBox) {
                setTimeout(function() {
                    alertBox.style.opacity = '0';
                    setTimeout(function() { alertBox.style.display = 'none'; }, 500);
                }, 10000);
            }

            function openOrderModal(type, id, name, price) {
                const modal = document.getElementById('orderModal');
                const form = document.getElementById('orderModalForm');
                const title = document.getElementById('modalItemTitle');
                const itemIdInput = document.getElementById('modalItemId');
                const itemPriceInput = document.getElementById('modalItemPrice');
                const submitBtn = document.getElementById('modalSubmitBtn');

                title.innerText = "Order: " + name;
                itemPriceInput.value = price;

                if (type === 'fnb') {
                    form.action = "<?php echo getUrlWithParams('fnb'); ?>";
                    itemIdInput.name = "fnb_id";
                    itemIdInput.id = "fnb_id";
                    itemIdInput.value = id;
                    submitBtn.name = "fnb_order";
                } else {
                    form.action = "<?php echo getUrlWithParams('combo'); ?>";
                    itemIdInput.name = "combo_id";
                    itemIdInput.id = "combo_id";
                    itemIdInput.value = id;
                    submitBtn.name = "combo_order";
                }

                modal.style.display = 'flex';
            }

            function closeOrderModal() { document.getElementById('orderModal').style.display = 'none'; }
            function openReceiptModal() { document.getElementById('receiptModal').style.display = 'flex'; }
            function closeReceiptModal() { document.getElementById('receiptModal').style.display = 'none'; }

            <?php if (isset($_GET['open_receipt'])): ?>
                openReceiptModal();
            <?php endif; ?>
        </script>
    </body>
    <?php if (file_exists("footer.html")) { include "footer.html"; } ?>
</html>