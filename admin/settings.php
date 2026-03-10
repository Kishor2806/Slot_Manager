<?php
// admin/settings.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_admin();

// In a full app, these would load from and save to a `settings` DB table.
// For this scaffolding, we provide the UI layout requested.
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mock save
    $success_msg = "Settings updated successfully! (Mocked - requires DB table for persistence)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - The Nexus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">

<!-- Admin Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">The Nexus Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="bookings.php">Bookings</a></li>
        <li class="nav-item"><a class="nav-link" href="whitelist.php">Whitelist</a></li>
        <li class="nav-item"><a class="nav-link" href="event_types.php">Event Types</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4 mb-5" style="max-width: 800px;">
    <h3 class="fw-bold mb-4">System Settings</h3>

    <?php if($success_msg): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST">
                
                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Email & SMTP (Zepto Mail)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" name="smtp_host" value="smtp.zeptomail.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" name="smtp_port" value="587">
                    </div>
                    <div class="col-12">
                        <label class="form-label">SMTP Username (Send Mail Token)</label>
                        <input type="text" class="form-control" name="smtp_user" value="emailapikey">
                    </div>
                    <div class="col-12">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" name="smtp_pass" value="********">
                    </div>
                </div>

                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Application Rules</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Timezone</label>
                        <select class="form-select" name="timezone">
                            <option value="Asia/Kolkata" selected>Asia/Kolkata (IST)</option>
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">America/New_York (EST)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Booking Window Limit (Days)</label>
                        <input type="number" class="form-control" name="window_limit" value="30">
                        <div class="form-text">Max days in advance a user can book.</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                          <input class="form-check-input" type="checkbox" id="auto_approve" name="auto_approve">
                          <label class="form-check-label" for="auto_approve">Auto-Approve internal bookings by default</label>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
