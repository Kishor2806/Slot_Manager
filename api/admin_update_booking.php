<?php
// api/admin_update_booking.php
require_once '../config.php';
require_once '../includes/middleware.php';

ini_set('display_errors', 0);
header('Content-Type: application/json');
require_admin(); // Admins only!

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']); exit;
}

$booking_id = $_POST['booking_id'] ?? '';
$action = $_POST['action'] ?? ''; // approved, cancelled, shift

if (empty($booking_id) || empty($action)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']); exit;
}

try {
    if ($action === 'approved' || $action === 'cancelled') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $action, 'id' => $booking_id]);
        
        // TODO: Send email notifying user of status change
        
    } elseif ($action === 'shift') {
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        
        if(strtotime($start_time) >= strtotime($end_time)) {
             echo json_encode(['success' => false, 'error' => 'Invalid time range']); exit;
        }

        // Note: Admin bypasses double-booking check to allow overrides, or we can enforce it.
        // For simplicity, we bypass, assuming admin knows what they are doing.
        
        $stmt = $pdo->prepare("UPDATE bookings SET start_time = :st, end_time = :et WHERE id = :id");
        $stmt->execute(['st' => date('Y-m-d H:i:s', strtotime($start_time)), 'et' => date('Y-m-d H:i:s', strtotime($end_time)), 'id' => $booking_id]);
        
        // TODO: Send email notifying user of shift
    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']); exit;
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
