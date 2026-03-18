<?php
// includes/middleware.php
require_once __DIR__ . '/../config.php';

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // Session Timeout Implementation (e.g., 30 minutes)
    $timeout_duration = 1800; // 30 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error_msg'] = "Session expired due to inactivity. Please log in again.";
        header("Location: login.php");
        exit();
    }
    $_SESSION['last_activity'] = time(); // Update last activity
}

function require_admin() {
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        die("Access Denied: You do not have permission to view this page.");
    }
}

function handle_auth_success($pdo, $email, $name, $zoho_id) {
    // All Zoho SSO authenticated users are allowed (no whitelist check needed).
    // Admin role is managed via the Manage Admins page in admin panel.

    // 1. Fetch or Create User
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // New users default to 'employee' role.
        // Admins can promote employees via the Manage Admins page.
        $role = 'employee';
        $stmt = $pdo->prepare("INSERT INTO users (name, email, role, zoho_id) VALUES (:name, :email, :role, :zoho_id)");
        $stmt->execute(['name' => $name, 'email' => $email, 'role' => $role, 'zoho_id' => $zoho_id]);
        $user_id = $pdo->lastInsertId();
    } else {
        $user_id = $user['id'];
        $role = $user['role'];
        // Update zoho ID if missing
        if (empty($user['zoho_id']) && !empty($zoho_id)) {
            $update = $pdo->prepare("UPDATE users SET zoho_id = :zoho_id WHERE id = :id");
            $update->execute(['zoho_id' => $zoho_id, 'id' => $user_id]);
        }
    }

    // 3. Set Session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['name'] = $name;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();

    // 4. Redirect
    if ($role === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}
?>
