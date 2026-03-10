<?php
// process.php
require_once 'config.php';

$action = $_GET['action'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($action) || empty($token)) {
    die("Error: Invalid link.");
}

try {
    // Look up booking by token
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE token = :token LIMIT 1");
    $stmt->execute(['token' => $token]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        die("Error: Token is invalid. It may have been used already or does not exist.");
    }

    if (strtotime($booking['token_expiry']) < time()) {
        die("Error: This link has expired.");
    }

    $b_id = $booking['id'];
    $current_status = $booking['status'];

    if ($action === 'approve') {
        if ($current_status === 'approved') die("Already approved.");
        
        $upd = $pdo->prepare("UPDATE bookings SET status = 'approved', token = NULL WHERE id = ?");
        $upd->execute([$b_id]);
        $msg = "Success! The booking has been approved.";
        
        // TODO: Send approval confirmation to user
        
    } elseif ($action === 'decline' || $action === 'cancel') {
        if ($current_status === 'cancelled') die("Already cancelled.");
        
        $upd = $pdo->prepare("UPDATE bookings SET status = 'cancelled', token = NULL WHERE id = ?");
        $upd->execute([$b_id]);
        $msg = "The booking has been successfully cancelled/declined.";
        
        // TODO: Send decline/cancellation info
        
    } else {
        die("Error: Unknown action.");
    }

} catch (PDOException $e) {
    die("Database error occurred.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Action Processed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow-sm p-4 text-center" style="max-width: 400px; width:100%; border-radius:12px;">
        <h4 class="fw-bold mb-3">Action Processed</h4>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <p class="text-muted small">This secure link is now one-time use and has been invalidated.</p>
        <a href="index.php" class="btn btn-outline-primary btn-sm mt-2">Return to Dashboard</a>
    </div>
</body>
</html>
