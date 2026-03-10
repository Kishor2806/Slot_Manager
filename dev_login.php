<?php
session_start();
require_once 'config.php';
require_once 'includes/middleware.php';

// Only allow if Zoho credentials are not configured (local dev)
if ($zoho_client_id !== 'YOUR_ZOHO_CLIENT_ID') {
    die("Dev mode is disabled.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $name = "Dev User";
    // Mock successful login flow
    handle_auth_success($pdo, $email, $name, 'mock_zoho_123');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Login - The Nexus</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow-sm p-4 text-center login-card" style="max-width: 400px; width: 100%; border-radius: 12px; border: none;">
        <h3 class="mb-4 fw-bold text-primary">Dev Auto Login</h3>
        <p class="text-muted small mb-4">Mock SSO login by entering an email. Make sure it is in the whitelist table (e.g. admin@example.com).</p>
        
        <form method="POST">
            <div class="mb-3 text-start">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="admin@example.com" required>
            </div>
            <button type="submit" class="btn btn-warning w-100 py-2 fw-bold" style="border-radius: 8px;">Login via Dev Mode</button>
            <a href="login.php" class="btn btn-link w-100 mt-2 text-decoration-none">Back to SSO</a>
        </form>
    </div>
</body>
</html>
