<?php
// api/book_slot.php
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
$event_id = $_POST['event_id'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$description = trim($_POST['description'] ?? '');

if (empty($event_id) || empty($start_time) || empty($end_time)) {
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
    // Check Double Booking
    // A double booking is when a new event overlaps with an existing non-cancelled event.
    // Overlap logic: (StartA < EndB) and (EndA > StartB)
    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE status != 'cancelled'
        AND start_time < :end_time 
        AND end_time > :start_time
        LIMIT 1
    ");
    $stmt->execute([
        'start_time' => date('Y-m-d H:i:s', $start_c),
        'end_time' => date('Y-m-d H:i:s', $end_c)
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'This slot is already booked or overlaps with an existing booking.']);
        exit;
    }

    // Generate secure token for actions later
    $token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+7 days'));

    // Insert
    $ins = $pdo->prepare("
        INSERT INTO bookings (user_id, event_id, start_time, end_time, description, status, token, token_expiry)
        VALUES (:ui, :ei, :st, :et, :desc, 'pending', :tk, :tke)
    ");
    $ins->execute([
        'ui' => $user_id,
        'ei' => $event_id,
        'st' => date('Y-m-d H:i:s', $start_c),
        'et' => date('Y-m-d H:i:s', $end_c),
        'desc' => $description,
        'tk' => $token,
        'tke' => $token_expiry
    ]);

    // TODO: Send Email Notification to HR & User here
    
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
