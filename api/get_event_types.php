<?php
// api/get_event_types.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_login();

header('Content-Type: application/json');

$stmt = $pdo->query("SELECT id, title, default_duration FROM master_events ORDER BY title ASC");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($types);
?>
