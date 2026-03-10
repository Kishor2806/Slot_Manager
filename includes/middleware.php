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
    // 1. Check Whitelist
    $domain = substr(strrchr($email, "@"), 1);
    
    $stmt = $pdo->prepare("SELECT * FROM whitelist WHERE (email_or_domain = :email OR email_or_domain = :domain) AND is_active = 1 LIMIT 1");
    $stmt->execute(['email' => $email, 'domain' => '@'.$domain]);
    $whitelist_entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$whitelist_entry) {
        $_SESSION['error_msg'] = "Access denied. Your email is not whitelisted.";
        header("Location: login.php");
        exit();
    }

    // 2. Fetch or Create User
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Create user. If they are the first user and the email matches admin@example.com they get admin, else employee.
        // For simplicity, we default to employee and let existing admins upgrade roles via DB/Dashboard.
        $role = ($email === 'admin@example.com') ? 'admin' : 'employee';
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
