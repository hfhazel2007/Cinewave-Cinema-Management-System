<?php
// 1. Centralized Auth & Database Connections
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$pdo = getDBConnection();

// Initial state variables
$error = "";
$fullname = '';
$phone_number = '';
$email = '';
$date_of_birth = '';

// Active tab selection
$active_tab = (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'register' : 'login';

// -----------------------------------------------------------------------------
// HANDLE LOGIN PROCESS
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $active_tab = 'login';
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (!$email || empty($password)) {
        $error = "Please provide a valid email address and password.";
    } else {
        // Step A: Check Customer Table
        $stmt = $pdo->prepare("
            SELECT membership_id, password, points, customer_group, membership_status 
            FROM customer 
            WHERE LOWER(email) = LOWER(:email)
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        // Normalize PostgreSQL boolean status flag
        $is_customer_active = $customer && ($customer['membership_status'] === true || $customer['membership_status'] === 't' || $customer['membership_status'] == 1);

        if ($is_customer_active && password_verify($password, $customer['password'])) {
            session_regenerate_id(true);

            $_SESSION['id']         = $customer['membership_id'];
            $_SESSION['user']       = "customer";
            $_SESSION['points']     = $customer['points'];
            $_SESSION['role']       = $customer['customer_group'];
            $_SESSION['food_order'] = false;

            header("Location: index.php");
            exit();
        } 

        // Step B: Check Staff Table if Customer check fails
        $stmtStaff = $pdo->prepare("
            SELECT staff_id, staff_password, account_status 
            FROM staff 
            WHERE LOWER(staff_email) = LOWER(:email)
            LIMIT 1
        ");
        $stmtStaff->execute([':email' => $email]);
        $staff = $stmtStaff->fetch(PDO::FETCH_ASSOC);

        $is_staff_active = $staff && ($staff['account_status'] === true || $staff['account_status'] === 't' || $staff['account_status'] == 1);

        if ($is_staff_active && password_verify($password, $staff['staff_password'])) {
            session_regenerate_id(true);

            $_SESSION['id']   = $staff['staff_id'];
            $_SESSION['user'] = "staff";
            $_SESSION['role'] = "admin";

            header("Location: cinewave_info_page.php");
            exit();
        }

        $error = "Invalid email or password.";
    }
}

// -----------------------------------------------------------------------------
// HANDLE REGISTRATION PROCESS
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $active_tab = 'register';

    $fullname         = trim($_POST['fullname'] ?? '');
    $phone_number     = trim($_POST['phone_number'] ?? '');
    $email            = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $date_of_birth    = $_POST['date_of_birth'] ?? '';
    $password         = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    // Form Validations
    if (!$email) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT membership_id FROM customer WHERE LOWER(email) = LOWER(:email)");
        $checkStmt->execute([':email' => $email]);

        if ($checkStmt->fetch()) {
            $error = "An account with this email address already exists.";
        } else {
            // Calculate Age Group
            $birth_date_obj   = new DateTime($date_of_birth);
            $current_date_obj = new DateTime();
            $age_years        = $current_date_obj->diff($birth_date_obj)->y;
            $customer_group   = ($age_years > 17) ? "Adult" : "Child";

            // Hash Password Safely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert New Customer using RETURNING for PostgreSQL ID retrieval
            $insertSql = "
                INSERT INTO customer (fullname, phone_number, email, date_of_birth, customer_group, password, points, membership_status, path) 
                VALUES (:fullname, :phone_number, :email, :dob, :group, :password, 0, true, 'asset/customer/user.png')
                RETURNING membership_id
            ";

            $stmtInsert = $pdo->prepare($insertSql);
            $stmtInsert->execute([
                ':fullname'     => $fullname,
                ':phone_number' => $phone_number,
                ':email'        => $email,
                ':dob'          => $date_of_birth,
                ':group'        => $customer_group,
                ':password'     => $hashed_password
            ]);

            $new_user_id = $stmtInsert->fetchColumn();

            if ($new_user_id) {
                session_regenerate_id(true);
                $_SESSION['id']     = $new_user_id;
                $_SESSION['user']   = "customer";
                $_SESSION['number'] = 1;
                $_SESSION['points'] = 0;
                $_SESSION['role']   = $customer_group;

                header("Location: index.php");
                exit();
            } else {
                $error = "Account creation failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - Cinewave</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="auth-layout">
        <section class="auth-copy">
            <span class="section-kicker">@CINEWAVE CINEMA</span>
            <h1 style="text-align: left;">THE<br><span>NEXT WAVE</span><br>STARTS HERE.</h1>
            <p>Register or sign in to book movies, select seats, and order food.</p>
        </section>

        <section class="auth-form-side">
            <div class="auth-box">
                <div class="auth-tabs">
                    <a href="login.php?tab=login" class="<?php echo ($active_tab === 'login') ? 'active' : ''; ?>">Login</a>
                    <a href="login.php?tab=register" class="<?php echo ($active_tab === 'register') ? 'active' : ''; ?>">Register</a>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($active_tab === 'register'): ?>
                    <h2>Create Account</h2>
                    <form action="login.php?tab=register" method="POST" autocomplete="off">
                        <div class="field">
                            <label for="fullname">Full Name</label>
                            <input id="fullname" type="text" name="fullname" value="<?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="field">
                            <label for="phone_number">Phone Number</label>
                            <input id="phone_number" type="tel" name="phone_number" value="<?php echo htmlspecialchars($phone_number, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="field">
                            <label for="date_of_birth">Date of Birth</label>
                            <input id="date_of_birth" type="date" name="date_of_birth" value="<?php echo htmlspecialchars($date_of_birth, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="field">
                            <label for="email">Email Address</label>
                            <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <div class="password-wrapper" style="position: relative; display: flex; align-items: center;">
                                <input id="password" type="password" name="password" required minlength="8" style="width: 100%; padding-right: 45px;">
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password', this)" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #888; font-size: 0.85rem;">Show</button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="password_confirm">Confirm Password</label>
                            <div class="password-wrapper" style="position: relative; display: flex; align-items: center;">
                                <input id="password_confirm" type="password" name="password_confirm" required minlength="8" style="width: 100%; padding-right: 45px;">
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password_confirm', this)" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #888; font-size: 0.85rem;">Show</button>
                            </div>
                        </div>

                        <br>

                        <button type="submit" name="register" class="submit-button">Register</button>
                    </form>

                <?php else: ?>
                    <h2>Sign In</h2>
                    <form action="login.php?tab=login" method="POST" autocomplete="off">
                        <div class="field">
                            <label for="login_email">Email Address</label>
                            <input id="login_email" type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="field">
                            <label for="login_password">Password</label>
                            <div class="password-wrapper" style="position: relative; display: flex; align-items: center;">
                                <input id="login_password" type="password" name="password" required style="width: 100%; padding-right: 45px;">
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('login_password', this)" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #888; font-size: 0.85rem;">Show</button>
                            </div>
                        </div>

                        <br>

                        <button type="submit" name="login" class="submit-button">Login</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

<script>
function togglePasswordVisibility(fieldId, btn) {
    const passwordInput = document.getElementById(fieldId);
    if (!passwordInput) return;

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        btn.textContent = "Hide";
    } else {
        passwordInput.type = "password";
        btn.textContent = "Show";
    }
}
</script>
</body>
</html>
