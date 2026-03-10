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

if (empty($booking_id)) {
    echo json_encode(['success' => false, 'error' => 'Booking ID missing.']);
    exit;
}

try {
    // Only allow cancelling if they own it
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :bid AND user_id = :uid");
    $stmt->execute(['bid' => $booking_id, 'uid' => $user_id]);
    
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
