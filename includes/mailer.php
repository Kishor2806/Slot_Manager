<?php
// includes/mailer.php
require_once __DIR__ . '/../config.php';

/**
 * Send an email using ZeptoMail API or fallback
 */
function send_email($to_email, $to_name, $subject, $html_content, $ics_content = null) {
    // ZeptoMail API Configuration
    // In production, these should come from env or config.php
    $url = "https://api.zeptomail.com/v1.1/email";
    $bounce_address = 'bounces@bounce.yourdomain.com';
    $from_address = 'noreply@yourdomain.com';
    $from_name = 'The Nexus';
    $send_mail_token = 'YOUR_ZEPTO_MAIL_TOKEN'; // from config or settings

    // Build Payload
    $payload = [
        "bounce_address" => $bounce_address,
        "from" => [
            "address" => $from_address,
            "name" => $from_name
        ],
        "to" => [
            [
                "email_address" => [
                    "address" => $to_email,
                    "name" => $to_name
                ]
            ]
        ],
        "subject" => $subject,
        "htmlbody" => $html_content
    ];

    if ($ics_content) {
        $payload['attachments'] = [
            [
                "content" => base64_encode($ics_content),
                "mime_type" => "text/calendar",
                "name" => "invite.ics"
            ]
        ];
    }

    /* 
    // Uncomment when ready to use actual ZeptoMail API
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Content-Type: application/json",
        "Authorization: Zoho-enczapikey " . $send_mail_token
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
    */
    
    // For local testing without valid SMTP credentials
    error_log("Mock Email Sent to: $to_email | Subject: $subject");
    return true;
}

/**
 * Generate iCalendar (ICS) content
 */
function generate_ics($start_time, $end_time, $summary, $description, $location="Office") {
    $dtstart = gmdate('Ymd\THis\Z', strtotime($start_time));
    $dtend = gmdate('Ymd\THis\Z', strtotime($end_time));
    $dtstamp = gmdate('Ymd\THis\Z');
    $uid = uniqid() . "@nexus";

    $ics = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//The Nexus//Slot Manager//EN\r\n";
    $ics .= "CALSCALE:GREGORIAN\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "DTSTART:$dtstart\r\n";
    $ics .= "DTEND:$dtend\r\n";
    $ics .= "DTSTAMP:$dtstamp\r\n";
    $ics .= "UID:$uid\r\n";
    $ics .= "SUMMARY:$summary\r\n";
    $ics .= "DESCRIPTION:$description\r\n";
    $ics .= "LOCATION:$location\r\n";
    $ics .= "STATUS:CONFIRMED\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";

    return $ics;
}

// Helper functions for common emails
function send_booking_confirmation($to_email, $to_name, $booking) {
    $subject = "Booking Confirmation: " . $booking['event_title'];
    
    // Generate secure links
    $base_url = "http://localhost/Slot%20Manager/"; 
    $cancel_link = $base_url . "process.php?action=cancel&token=" . $booking['token'];

    $html = "
        <h2>Your Booking is Pending Approval</h2>
        <p>Hi {$to_name},</p>
        <p>You have booked <strong>{$booking['event_title']}</strong> on " . date('M d, Y', strtotime($booking['start_time'])) . " from " . date('h:i A', strtotime($booking['start_time'])) . " to " . date('h:i A', strtotime($booking['end_time'])) . ".</p>
        <p>Status: <span style='color:orange;'>Pending</span></p>
        <br>
        <p>If you need to cancel this request before approval, <a href='{$cancel_link}' style='color:red;'>click here to cancel</a>.</p>
    ";

    $ics = generate_ics($booking['start_time'], $booking['end_time'], $booking['event_title'], $booking['description']);

    return send_email($to_email, $to_name, $subject, $html, $ics);
}

function send_admin_notification($booking) {
    // Ideally grab admin emails from DB
    $admin_email = "admin@example.com";
    $subject = "New Booking Request: " . $booking['event_title'];
    
    $base_url = "http://localhost/Slot%20Manager/"; 
    $approve_link = $base_url . "process.php?action=approve&token=" . $booking['token'];
    $decline_link = $base_url . "process.php?action=decline&token=" . $booking['token'];

    $html = "
        <h2>New Booking Request</h2>
        <p><strong>Employee:</strong> {$booking['user_name']}</p>
        <p><strong>Event:</strong> {$booking['event_title']}</p>
        <p><strong>Time:</strong> " . date('M d, Y h:i A', strtotime($booking['start_time'])) . " to " . date('h:i A', strtotime($booking['end_time'])) . "</p>
        <br>
        <a href='{$approve_link}' style='padding:10px 15px; background:green; color:white; text-decoration:none; border-radius:5px;'>Approve</a>
        &nbsp;&nbsp;
        <a href='{$decline_link}' style='padding:10px 15px; background:red; color:white; text-decoration:none; border-radius:5px;'>Decline</a>
    ";

    return send_email($admin_email, "Admin", $subject, $html);
}
?>
