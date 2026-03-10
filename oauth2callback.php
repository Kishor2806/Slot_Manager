<?php
require_once 'config.php';
require_once 'includes/middleware.php';

if (isset($_GET['code'])) {
    // Verify state
    if (empty($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        $_SESSION['error_msg'] = "Invalid state parameter. Possible CSRF attack.";
        header("Location: login.php");
        exit();
    }

    $code = $_GET['code'];

    // 1. Exchange code for access token
    $token_url = "https://accounts.zoho.com/oauth/v2/token";
    $post_data = [
        'grant_type' => 'authorization_code',
        'client_id' => $zoho_client_id,
        'client_secret' => $zoho_client_secret,
        'redirect_uri' => $zoho_redirect_uri,
        'code' => $code
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // 2. Fetch user profile from Zoho
        // Note: The specific API endpoint depends on the Zoho scope requested.
        // Assuming Zoho profile API:
        $profile_url = "https://contacts.zoho.com/file?fs=us"; // Example URL, actual endpoint varies in Zoho ecosystem
        
        // For demonstration purposes, if Zoho OAuth is mocked or if we just want a placeholder until real API is plugged in:
        // You'll need to hit the exact Zoho API you enabled. e.g. Zoho CRM Users API:
        $user_api_url = "https://www.zohoapis.com/crm/v3/users?type=CurrentUser";

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $user_api_url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            "Authorization: Zoho-oauthtoken " . $access_token
        ]);
        $profile_response = curl_exec($ch2);
        curl_close($ch2);

        $profile_data = json_decode($profile_response, true);

        if (isset($profile_data['users'][0])) {
            $user_info = $profile_data['users'][0];
            $email = $user_info['email'];
            $name = $user_info['full_name'];
            $zoho_id = $user_info['id'];

            // Handle success
            handle_auth_success($pdo, $email, $name, $zoho_id);

        } else {
            $_SESSION['error_msg'] = "Failed to retrieve user profile from Zoho.";
            header("Location: login.php");
            exit();
        }

    } else {
        $_SESSION['error_msg'] = "Failed to obtain access token from Zoho.";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
