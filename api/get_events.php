<?php
// api/get_events.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_login();

header('Content-Type: application/json');

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

if (!$start || !$end) {
    echo json_encode([]);
    exit;
}

// Fetch all events within the date range
$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.user_id,
        b.start_time as start,
        b.end_time as end,
        b.status,
        b.description,
        b.event_id,
        me.title,
        me.color_code,
        u.name as user_name
    FROM bookings b
    JOIN master_events me ON b.event_id = me.id
    JOIN users u ON b.user_id = u.id
    WHERE b.start_time >= :start AND b.end_time <= :end
");

$stmt->execute(['start' => $start, 'end' => $end]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];

foreach ($results as $row) {
    // Determine color based on status
    $color = $row['color_code']; // fallback
    if ($row['status'] === 'approved') {
        $color = '#28a745'; // Green
    } elseif ($row['status'] === 'pending') {
        $color = '#ffc107'; // Yellow
    } elseif ($row['status'] === 'cancelled') {
        $color = '#6c757d'; // Grey
    }

    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'] . ' (' . $row['user_name'] . ')',
        'start' => $row['start'],
        'end' => $row['end'],
        'color' => $color,
        'extendedProps' => [
            'status' => $row['status'],
            'description' => $row['description'],
            'user_id' => $row['user_id'],
            'user_name' => $row['user_name'],
            'event_id' => $row['event_id'],
            'themeColor' => $row['color_code'] // original theme color of event
        ]
    ];
}

echo json_encode($events);
?>
