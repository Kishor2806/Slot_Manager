<?php
// api/cancel_own_slot.php
require_once '../config.php';
require_once '../includes/middleware.php';

ini_set('display_errors', 0);
header('Content-Type: application/json');
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$booking_id = $_POST['booking_id'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'employee';

if (empty($booking_id)) {
    echo json_encode(['success' => false, 'error' => 'Booking ID missing.']);
    exit;
}

try {
    // Admins can cancel any booking. Normal employees can only cancel their own.
    if ($user_role === 'admin' || $user_role === 'super_admin') {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :bid");
        $stmt->execute(['bid' => $booking_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :bid AND user_id = :uid");
        $stmt->execute(['bid' => $booking_id, 'uid' => $user_id]);
    }
    
    if ($stmt->rowCount() > 0) {
        // TODO: Send cancellation email
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not cancel booking. You might not have permission, or it does not exist.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
