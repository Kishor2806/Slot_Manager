<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'nexus_booking';

// Zoho Settings
$zoho_client_id = 'YOUR_ZOHO_CLIENT_ID';
$zoho_client_secret = 'YOUR_ZOHO_CLIENT_SECRET';
$zoho_redirect_uri = 'http://localhost/Slot%20Manager/oauth2callback.php';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Enable emulated prepares off for true prepared statements
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

date_default_timezone_set('Asia/Kolkata');
?>
