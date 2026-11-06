<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $group_booking_id = intval($_POST['group_booking_id']);
    $group_booking_name = trim($_POST['group_booking_name']);
    $group_booking_status = trim($_POST['group_booking_status']);
    $staff_id = intval($_POST['staff_id']);
    $update_sql = "UPDATE group_booking_order gbo
                    JOIN group_booking gb ON gbo.group_booking_id=gb.group_booking_id
                    SET gb.group_booking_name=?,gb.group_booking_status=?,gbo.staff_id=?
                    WHERE gb.group_booking_id=?";
    $stmt = $pdo->prepare($update_sql);
    if ($stmt) {
    try {
        // Pass parameters directly into execute()
        $success = $stmt->execute([
            $group_booking_name,
            $group_booking_status,
            $staff_id,
            $group_booking_id
        ]);

        if ($success) {
            echo "<script>alert('Update Group Booking Success!');window.location.href=window.location.href;</script>";
        } else {
            $error = $stmt->errorInfo()[2];
            echo "<script>alert('Update Fail!: " . addslashes($error) . "');window.location.href=window.location.href;</script>";
        }
    } catch (PDOException $e) {
        // Catch foreign key constraint violation (e.g. invalid staff_id in Postgres)
        echo "<script>alert('Save Fail : Staff ID not exist');</script>";
    }
}
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $group_booking_id = intval($_POST['group_booking_id']);
    $delete_sql = "DELETE FROM group_booking WHERE group_booking_id = ?";
    $stmt = $pdo->prepare($delete_sql);
    if ($stmt) {
    // 1. Pass parameters directly inside an array to execute()
    $success = $stmt->execute([$group_booking_id]);

    if ($success) {
        echo "<script>alert('Group Booking Successfully Deleted!');window.location.href=window.location.href;</script>";
    } else {
        // 2. Use $stmt->errorInfo()[2] to get PDO error messages
        $error = $stmt->errorInfo()[2];
        echo "<script>alert('Fail to delete: " . addslashes($error) . "');window.location.href=window.location.href;</script>";
    }
}
}
?>

<?php
$sql = "SELECT gb.group_booking_id,gb.group_booking_name,gb.group_booking_status,gbo.staff_id,s.date AS booking_date,s.start_time,m.movie_name,h.hall_number
 FROM group_booking gb
 LEFT JOIN group_booking_order gbo ON gb.group_booking_id = gbo.group_booking_id
 LEFT JOIN total_order t_o ON gbo.group_booking_order_id = t_o.group_booking_order_id
 LEFT JOIN ticket t ON t_o.ticket_id = t.ticket_id
 LEFT JOIN showtime s ON t.showtime_id = s.showtime_id
 LEFT JOIN movie m ON s.movie_id = m.movie_id
 LEFT JOIN hall h ON s.hall_id=h.hall_id";

$result = $pdo->query($sql);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Cinewave_info_page</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body class="body">
    <div class="container">
        <div>
            <h2>Group Booking Management Details</h2>
            <?php
            if ($result && $result->rowCount() > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Group Name</th>
                            <th>Movie Name</th>
                            <th>Hall Number</th>
                            <th>Booking Date</th>
                            <th>Start Time</th>
                            <th>Status</th>
                            <th>Staff ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $result->fetch(PDO::FETCH_ASSOC)):
                            $form_id = "form_gb" . $booking['group_booking_id'];
                            $status_pending = ($booking['group_booking_status'] == 'Pending') ? "selected" : "";
                            $status_confirmed = ($booking['group_booking_status'] == 'Confirmed') ? "selected" : "";
                            $status_cancelled = ($booking['group_booking_status'] == 'Cancelled') ? "selected" : "";
                        ?>
                            <tr>
                                <form id="<?php echo $form_id; ?>" method="POST" action="">
                                    <input type="hidden" name="group_booking_id" value="<?php echo $booking['group_booking_id']; ?>">
                                </form>

                                <td>
                                    <?php echo $booking['group_booking_id']; ?>
                                </td>

                                <td><input type="text" name="group_booking_name" form="<?php echo $form_id; ?>" value="<?php echo htmlspecialchars($booking['group_booking_name']); ?>" required></td>

                                <td>
                                    <?php echo htmlspecialchars($booking['movie_name']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['hall_number']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['booking_date']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['start_time']); ?>
                                </td>

                                <td>
                                    <select name="group_booking_status" form="<?php echo $form_id; ?>" required>
                                        <option value="Pending" <?php echo $status_pending; ?>>Pending</option>
                                        <option value="Confirmed" <?php echo $status_confirmed; ?>>Confirmed</option>
                                        <option value="Cancelled" <?php echo $status_cancelled; ?>>Cancelled</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="number" name="staff_id" form="<?php echo $form_id; ?>" value="<?php echo intval($booking['staff_id']); ?>" required>

                                </td>

                                <td>
                                    <button type="submit" name="action" value="update" form="<?php echo $form_id; ?>" class="btn" onclick="return confirm('Are You Sure Update Booking for This?')">Save</button>
                                    <button type="submit" name="action" value="delete" form="<?php echo $form_id; ?>" class="btn" onclick="return confirm('Are You Sure Delete Group Booking for This?')">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No group booking found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>



</html>