<?php
// 1. Ensure Session is Started Safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Centralized Database Connection (if needed across layout components)
require_once __DIR__ . '/config/database.php';

// 3. Handle Logout Action BEFORE Any HTML Output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    session_write_close();
    header("Location: index.php");
    exit();
}

// Determine User State & Role
$isLoggedIn = isset($_SESSION['user']);
$userRole   = $_SESSION['user'] ?? null;
?>
<header>
    <?php if (!$isLoggedIn || $userRole === 'customer'): ?>
        <!-- Customer or Guest Brand Logo -->
        <a href="index.php">
            <h1>Cinewave</h1>
        </a>
    <?php else: ?>
        <!-- Staff Brand Logo -->
        <a href="cinewave_info_page.php">
            <h1>Cinewave</h1>
        </a>
    <?php endif; ?>

    <nav>
        <?php if (!$isLoggedIn): ?>
            <!-- Guest Navigation -->
            <a href="movie_catalogue.php">Movie</a>
            <a href="fnb_order.php">Food and Beverage</a>
            <a href="promotion.php">Promotion</a>
            <a href="login.php" class="btn btn-register">Login/Register</a>

        <?php elseif ($userRole === 'customer'): ?>
            <!-- Customer Navigation -->
            <a href="movie_catalogue.php">Movie</a>
            <a href="fnb_order.php">Food and Beverage</a>
            <a href="promotion.php">Promotion</a>
            <a href="reward.php">Reward</a>
            <a href="aboutme.php">Me</a>

        <?php elseif ($userRole === 'staff'): ?>
            <!-- Staff / Admin Navigation -->
            <a href="booking_management.php">Booking</a>
            <a href="moviemanagement.php">Movie</a>
            <a href="fnbmanagement.php">FNB</a>
            <a href="showtime_management.php">Showtime</a>
            <a href="hall_management.php">Hall</a>
            <a href="promotion_management.php">Promotion</a>
            <a href="reward_management.php">Reward</a>
            
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST" style="display: inline;">
                <button type="submit" name="logout" class="btn btn-logout">Log Out</button>
            </form>
        <?php endif; ?>
    </nav>
</header>