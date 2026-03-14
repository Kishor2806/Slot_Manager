<?php
// api/edit_slot.php
require_once '../config.php';
require_once '../includes/middleware.php';

// Disable default HTML error output for APIs
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'employee';

$booking_id = $_POST['booking_id'] ?? '';
$event_id = $_POST['event_id'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$description = trim($_POST['description'] ?? '');

if (empty($booking_id) || empty($event_id) || empty($start_time) || empty($end_time)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

$start_c = strtotime($start_time);
$end_c = strtotime($end_time);

if ($start_c >= $end_c) {
    echo json_encode(['success' => false, 'error' => 'End time must be after start time.']);
    exit;
}

if ($start_c < time()) {
    echo json_encode(['success' => false, 'error' => 'Cannot book past dates.']);
    exit;
}

try {
    // Determine if the user is authorized to edit this booking
    if ($user_role !== 'admin' && $user_role !== 'super_admin') {
        $check_stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = :bid AND user_id = :uid");
        $check_stmt->execute(['bid' => $booking_id, 'uid' => $user_id]);
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'You do not have permission to edit this slot.']);
            exit;
        }
    } else {
        // Just verify it exists
        $check_stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = :bid");
        $check_stmt->execute(['bid' => $booking_id]);
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Booking not found.']);
            exit;
        }
    }

    // Check Double Booking against OTHER non-cancelled events
    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE status != 'cancelled'
        AND id != :current_booking_id
        AND start_time < :end_time 
        AND end_time > :start_time
        LIMIT 1
    ");
    $stmt->execute([
        'current_booking_id' => $booking_id,
        'start_time' => date('Y-m-d H:i:s', $start_c),
        'end_time' => date('Y-m-d H:i:s', $end_c)
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'This edited slot overlaps with an existing booking.']);
        exit;
    }

    // Perform Update
    $upd = $pdo->prepare("
        UPDATE bookings 
        SET event_id = :ei, start_time = :st, end_time = :et, description = :desc 
        WHERE id = :bid
    ");
    $upd->execute([
        'ei' => $event_id,
        'st' => date('Y-m-d H:i:s', $start_c),
        'et' => date('Y-m-d H:i:s', $end_c),
        'desc' => $description,
        'bid' => $booking_id
    ]);

    // TODO: Send Edit Notification Email to HR & User here if needed
    
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
