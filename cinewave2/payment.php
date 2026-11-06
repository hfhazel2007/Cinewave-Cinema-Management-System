<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

// Initialize session cart array if not present
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. Resolve Ticket & Membership Parameters Safely
if (isset($_GET['id'])) {
    $seat_id = intval($_GET['id']);
} elseif (isset($_SESSION['seat_id'])) {
    $seat_id = intval($_SESSION['seat_id']);
} else {
    $seat_id = 1001;
}

$showtime_id   = isset($_SESSION['showtime_id']) ? intval($_SESSION['showtime_id']) : 1001;
$ticket_price  = isset($_SESSION['ticket_price']) ? floatval($_SESSION['ticket_price']) : 12.50;
$ticket_type   = isset($_SESSION['ticket_type']) ? $_SESSION['ticket_type'] : 'Adult';

$membership_id = isset($_SESSION['id']) ? intval($_SESSION['id']) : 1001;

// Fetch active points for membership user
$user_points_stmt = $pdo->prepare("SELECT points FROM customer WHERE membership_id = :id");
$user_points_stmt->execute([':id' => $membership_id]);
$p_row = $user_points_stmt->fetch(PDO::FETCH_ASSOC);

$points = $p_row ? intval($p_row['points']) : (isset($_SESSION['points']) ? intval($_SESSION['points']) : 0);

// 2. Calculate Base Food & Beverage Cart Total & Subtotal
$fnb_cart_total = 0.00;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cart_item) {
        $fnb_cart_total += floatval($cart_item['total']);
    }
}

$subtotal = $ticket_price + $fnb_cart_total; // Base total before discount
$amount_paid = $subtotal;                   // Default total

// 3. Process Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $payment_method = $_POST['payment_method'] ?? 'Credit Card';
    $promotion_name = $_POST['promotion_name'] ?? '';
    $reward_name    = $_POST['reward_name'] ?? '';
    
    $promo_discount_rate = 0.00;
    $reward_discount_val = 0.00;
    $points_to_deduct    = 0;

    // Resolve Promotion ID & Rate
    $promotion_id = null;
    if (!empty($promotion_name)) {
        $p_stmt = $pdo->prepare("SELECT promotion_id, discount_percentage FROM promotion WHERE promotion_name = :name");
        $p_stmt->execute([':name' => $promotion_name]);
        if ($p_row = $p_stmt->fetch(PDO::FETCH_ASSOC)) { 
            $promotion_id = intval($p_row['promotion_id']); 
            $promo_discount_rate = floatval($p_row['discount_percentage']);
        }
    }
    
    // Resolve Reward ID, Points Required & Discount Value
    $reward_id = null;
    if (!empty($reward_name)) {
        $r_stmt = $pdo->prepare("SELECT reward_id, reward_name, points_required FROM reward WHERE reward_name = :name");
        $r_stmt->execute([':name' => $reward_name]);
        if ($r_row = $r_stmt->fetch(PDO::FETCH_ASSOC)) { 
            $reward_id        = intval($r_row['reward_id']); 
            $points_to_deduct = intval($r_row['points_required']);
            $r_name_lower     = strtolower($r_row['reward_name']);

            // Calculate monetary deduction based on reward name
            if (strpos($r_name_lower, 'free movie ticket') !== false) {
                $reward_discount_val = $ticket_price;
            } elseif (strpos($r_name_lower, 'rm10') !== false || strpos($r_name_lower, '10 f&b') !== false) {
                $reward_discount_val = 10.00;
            }
        }
    }

    // Apply combined promotion percentage and reward discount
    $amount_paid = max(0, ($subtotal * (1 - $promo_discount_rate)) - $reward_discount_val);

    // Calculate newly earned points (1 point per $1 spent)
    $points_earned = floor($amount_paid);

    // A. Insert Ticket First
    $sql_ticket = "INSERT INTO ticket (ticket_type, ticket_price, path, showtime_id, seat_id) 
                   VALUES (:type, :price, 'asset/ticket/qr.png', :showtime, :seat)";
    $stmt_ticket = $pdo->prepare($sql_ticket);
    
    if ($stmt_ticket->execute([
        ':type'     => $ticket_type,
        ':price'    => $ticket_price,
        ':showtime' => $showtime_id,
        ':seat'     => $seat_id
    ])) {
        $ticket_id = $pdo->lastInsertId('ticket_ticket_id_seq');
        $current_datetime = date("Y-m-d H:i:s");

        $last_fnb_item_id   = null;
        $last_combo_item_id = null;

        // B. STEP 1: Insert FNB & Combo Items into their own entities FIRST & grab IDs
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $item_id  = intval($item['item_id']);
                $quantity = intval($item['quantity']);
                $total    = floatval($item['total']);
                $remarks  = $item['remarks'] ?? '';

                if ($item['type'] === 'Combo') {
                    if ($item_id > 0) {
                        $check_combo = $pdo->prepare("SELECT combo_id FROM combo WHERE combo_id = :id");
                        $check_combo->execute([':id' => $item_id]);
                        if ($check_combo->rowCount() > 0) {
                            $sql_combo = "INSERT INTO combo_item (combo_quantity, remarks, combo_id) 
                                          VALUES (:quantity, :remarks, :combo_id)";
                            $stmt_combo = $pdo->prepare($sql_combo);
                            if ($stmt_combo->execute([':quantity' => $quantity, ':remarks' => $remarks, ':combo_id' => $item_id])) {
                                $last_combo_item_id = $pdo->lastInsertId('combo_item_combo_item_id_seq');
                            }
                        }
                    }
                } else {
                    if ($item_id > 0) {
                        $check_fnb = $pdo->prepare("SELECT fnb_id FROM fnb WHERE fnb_id = :id");
                        $check_fnb->execute([':id' => $item_id]);
                        if ($check_fnb->rowCount() > 0) {
                            $sql_fnb = "INSERT INTO fnb_item (quantity, fnb_total_price, fnb_item_status, remarks, fnb_id) 
                                        VALUES (:quantity, :total, 'Preparing', :remarks, :fnb_id)";
                            $stmt_fnb = $pdo->prepare($sql_fnb);
                            if ($stmt_fnb->execute([':quantity' => $quantity, ':total' => $total, ':remarks' => $remarks, ':fnb_id' => $item_id])) {
                                $last_fnb_item_id = $pdo->lastInsertId('fnb_item_fnb_item_id_seq');
                            }
                        }
                    }
                }
            }
        }

        // C. STEP 2: Insert Total Order containing retrieved fnb_item_id and combo_item_id
        $sql_order = "INSERT INTO total_order (total_order_datetime, membership_id, promotion_id, reward_id, ticket_id, fnb_item_id, combo_item_id, group_booking_order_id) 
                      VALUES (:dt, :membership_id, :promotion_id, :reward_id, :ticket_id, :fnb_item_id, :combo_item_id, NULL)";
        $stmt_order = $pdo->prepare($sql_order);

        if ($stmt_order->execute([
            ':dt'           => $current_datetime,
            ':membership_id'=> $membership_id,
            ':promotion_id' => $promotion_id,
            ':reward_id'    => $reward_id,
            ':ticket_id'    => $ticket_id,
            ':fnb_item_id'  => $last_fnb_item_id,
            ':combo_item_id'=> $last_combo_item_id
        ])) {
            $total_order_id = $pdo->lastInsertId('total_order_total_order_id_seq');

            // D. STEP 3: Insert Payment Record linked to total_order_id
            $txn_ref = "TXN" . date("Ymd") . rand(1000, 9999);
            $sql_payment = "INSERT INTO payment (payment_method, amount_paid, transaction_reference, payment_status, payment_datetime, total_order_id) 
                            VALUES (:method, :amount, :ref, 'Paid', :dt, :total_order_id)";
            $stmt_payment = $pdo->prepare($sql_payment);
                
            if ($stmt_payment->execute([
                ':method'         => $payment_method,
                ':amount'         => $amount_paid,
                ':ref'            => $txn_ref,
                ':dt'             => $current_datetime,
                ':total_order_id' => $total_order_id
            ])) {
                // E. Update Membership Points (Deduct redeemed points & add earned points from spending)
                $updated_points = max(0, $points - $points_to_deduct) + $points_earned;
                
                $update_pts_stmt = $pdo->prepare("UPDATE customer SET points = :points WHERE membership_id = :id");
                $update_pts_stmt->execute([':points' => $updated_points, ':id' => $membership_id]);
                
                $_SESSION['points'] = $updated_points;

                unset($_SESSION['cart']);
                unset($_SESSION['ticket_type'], $_SESSION['ticket_price'], $_SESSION['seat_id']);

                echo "<script>alert('Payment Successful! You earned {$points_earned} points.'); window.location.href='index.php';</script>";
                exit();
            } else {
                $error = $stmt_payment->errorInfo()[2] ?? 'Unknown payment error';
                echo "<script>alert('Payment Error: " . addslashes($error) . "');</script>";
            }
        } else {
            $error = $stmt_order->errorInfo()[2] ?? 'Unknown order error';
            echo "<script>alert('Order Error: " . addslashes($error) . "');</script>";
        }
    } else {
        $error = $stmt_ticket->errorInfo()[2] ?? 'Unknown ticket error';
        echo "<script>alert('Ticket Error: " . addslashes($error) . "');</script>";
    }
}


// 4. Fetch Movie & Seat Display Data 
$movie_sql = "SELECT s.date, s.start_time, m.path, m.movie_name 
              FROM showtime s 
              LEFT JOIN movie m ON s.movie_id = m.movie_id 
              WHERE s.showtime_id = :showtime_id";
$movie_stmt = $pdo->prepare($movie_sql);
$movie_stmt->execute([':showtime_id' => $showtime_id]);
$movie_info = ($movie_stmt->rowCount() > 0) ? $movie_stmt->fetch(PDO::FETCH_ASSOC) : ['date'=>'N/A', 'start_time'=>'N/A', 'path'=>'', 'movie_name'=>'Movie'];

$seat_stmt = $pdo->prepare("SELECT seat_number FROM seat WHERE seat_id = :seat_id");
$seat_stmt->execute([':seat_id' => $seat_id]);
$seat_info = ($seat_stmt->rowCount() > 0) ? $seat_stmt->fetch(PDO::FETCH_ASSOC) : ['seat_number' => 'A' . $seat_id];

// 5. Fetch User Profile Data & Evaluate Promotion Restrictions
$user_stmt = $pdo->prepare("SELECT date_of_birth, membership_status FROM customer WHERE membership_id = :id");
$user_stmt->execute([':id' => $membership_id]);
$user_data = ($user_stmt->rowCount() > 0) ? $user_stmt->fetch(PDO::FETCH_ASSOC) : ['date_of_birth' => null, 'membership_status' => '1'];

// Calculate Age and DateTime Context
$user_age = 0;
if (!empty($user_data['date_of_birth'])) {
    $dob = new DateTime($user_data['date_of_birth']);
    $today = new DateTime();
    $user_age = $today->diff($dob)->y;
}
$current_day_name = date('l'); 
$current_month = date('m');    
$user_birth_month = !empty($user_data['date_of_birth']) ? date('m', strtotime($user_data['date_of_birth'])) : '';

$eligible_promotions = [];
$p_list = $pdo->query("SELECT * FROM promotion");

if ($p_list && $p_list->rowCount() > 0) {
    while ($p_row = $p_list->fetch(PDO::FETCH_ASSOC)) {
        $p_cond = strtolower($p_row['conditions']);
        $is_eligible = true;

        if (strpos($p_cond, 'senior citizen') !== false || strpos($p_cond, '60 years') !== false) {
            if ($user_age < 60) { $is_eligible = false; }
        }
        if (strpos($p_cond, 'tuesday') !== false) {
            if ($current_day_name !== 'Tuesday') { $is_eligible = false; }
        }
        if (strpos($p_cond, 'members only') !== false || strpos($p_cond, 'cinewave members') !== false) {
            if (!isset($_SESSION['id']) || strtolower($user_data['membership_status']) === 'guest') { $is_eligible = false; }
        }
        if (strpos($p_cond, 'birthday') !== false) {
            if (empty($user_birth_month) || $user_birth_month !== $current_month) { $is_eligible = false; }
        }

        if ($is_eligible) {
            $eligible_promotions[] = $p_row;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Payment</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Quantico:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .cart-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 20px 0;
            background-color: #1e1e2f;
            color: #ffffff;
            border-radius: 6px;
            overflow: hidden;
        }
        .cart-summary-table th, .cart-summary-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #333;
            text-align: left;
            font-size: 0.9em;
        }
        .cart-summary-table th {
            background-color: #2a2a40;
            color: #f5f6fa;
        }
    </style>
</head>
<body class="body">
    <h1>Payment Summary</h1>
    
    <form method="POST" action="?id=<?php echo $seat_id; ?>">
        <section class="container">
            <?php if (!empty($movie_info['path'])): ?>
                <img src="<?php echo htmlspecialchars($movie_info['path']); ?>" alt="Movie Poster">
            <?php endif; ?>
            <div>
                <h3>Movie:</h3>
                <p><?php echo htmlspecialchars($movie_info['movie_name']); ?></p>
                
                <h3>Showtime:</h3>
                <p><?php echo htmlspecialchars($movie_info['date']) . " @ " . ($movie_info['start_time'] !== 'N/A' ? date("g:i A", strtotime($movie_info['start_time'])) : 'N/A'); ?></p>
                
                <h3>Seat Allocated:</h3>
                <p><?php echo htmlspecialchars($seat_info['seat_number']); ?></p>
                
                <h3>Ticket Selection:</h3>
                <p><?php echo htmlspecialchars($ticket_type); ?> ($<?php echo number_format($ticket_price, 2); ?>)</p>

                <h3>Food & Beverage Selection:</h3>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <table class="cart-summary-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $c_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c_item['name']); ?> <small>(<?php echo htmlspecialchars($c_item['type']); ?>)</small></td>
                                    <td><?php echo $c_item['quantity']; ?></td>
                                    <td>$<?php echo number_format($c_item['unit_price'], 2); ?></td>
                                    <td>$<?php echo number_format($c_item['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #aaa;">No food or combo items added.</p>
                <?php endif; ?>

                <!-- Dynamic Promotion Dropdown -->
                <label for="promotion_name">Select Promotion</label>
                <select name="promotion_name" id="promotion_name" onchange="updateTotalAmount()">
                    <option value="" data-discount="0">None</option>
                    <?php if (!empty($eligible_promotions)): ?>
                        <?php foreach ($eligible_promotions as $p_item): ?>
                            <?php $disc = floatval($p_item['discount_percentage']); ?>
                            <option value="<?php echo htmlspecialchars($p_item['promotion_name']); ?>" data-discount="<?php echo $disc; ?>">
                                <?php echo htmlspecialchars($p_item['promotion_name']); ?> 
                                (<?php echo ($disc * 100); ?>% Off)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>   

                <!-- Dynamic Reward Dropdown (Filters by Points & Expiration Date) -->
                <label for="reward_name">Select Reward Redemption (Available Points: <?php echo $points; ?>)</label>
                <select name="reward_name" id="reward_name" onchange="updateTotalAmount()">
                    <option value="" data-reward-discount="0">None</option>
                    <?php 
                    $today_date = date("Y-m-d");
                    $r_list = $pdo->query("SELECT reward_name, points_required, exp_date FROM reward WHERE exp_date >= '$today_date'");
                    
                    if ($r_list && $r_list->rowCount() > 0) {
                        while ($r_row = $r_list->fetch(PDO::FETCH_ASSOC)) {
                            $pts_req = intval($r_row['points_required']);
                            $r_name  = htmlspecialchars($r_row['reward_name']);
                            $r_name_lower = strtolower($r_name);

                            $r_disc = 0.00;
                            if (strpos($r_name_lower, 'free movie ticket') !== false) {
                                $r_disc = $ticket_price;
                            } elseif (strpos($r_name_lower, 'rm10') !== false || strpos($r_name_lower, '10 f&b') !== false) {
                                $r_disc = 10.00;
                            }

                            if ($points >= $pts_req) {
                                echo "<option value='{$r_name}' data-reward-discount='{$r_disc}'>{$r_name} ({$pts_req} pts)</option>";
                            }
                        }
                    }
                    ?>
                </select> 

                <h3>Total Due Amount:</h3>
                <p><strong id="display_total">$<?php echo number_format($subtotal, 2); ?></strong></p>

                <label for="payment_method">Select Payment Method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="Credit Card">Credit Card</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Online Banking">Online Banking</option>
                </select>   
            </div>
        </section>
        
        <section class="container">
            <button type="submit" name="submit_payment" id="submit_btn" class="btn">
                Confirm & Pay ($<?php echo number_format($subtotal, 2); ?>)
            </button>
        </section>
    </form>

    <script>
        const baseSubtotal = <?php echo floatval($subtotal); ?>;

        function updateTotalAmount() {
            const promoSelect = document.getElementById('promotion_name');
            const promoOption = promoSelect.options[promoSelect.selectedIndex];
            const promoRate   = parseFloat(promoOption.getAttribute('data-discount')) || 0;

            const rewardSelect = document.getElementById('reward_name');
            const rewardOption = rewardSelect.options[rewardSelect.selectedIndex];
            const rewardDeduct = parseFloat(rewardOption.getAttribute('data-reward-discount')) || 0;

            let finalTotal = (baseSubtotal * (1 - promoRate)) - rewardDeduct;
            if (finalTotal < 0) finalTotal = 0;

            document.getElementById('display_total').innerText = '$' + finalTotal.toFixed(2);
            document.getElementById('submit_btn').innerText = 'Confirm & Pay ($' + finalTotal.toFixed(2) + ')';
        }
    </script>
</body>
<?php if (file_exists("footer.html")) { include "footer.html"; } ?>
</html>