<?php
/**
 * Contact Form Process Script
 */

header('Content-Type: application/json');

require_once 'sendmail.php';
require_once 'email-template.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Form fields
$firstname = sanitize($_POST['firstname'] ?? '');
$lastname = sanitize($_POST['lastname'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$telno = sanitize($_POST['telno'] ?? '');
$msg = sanitize($_POST['msg'] ?? '');
$captcha_response = $_POST['cf-turnstile-response'] ?? '';

// Validate required fields
if (empty($firstname) || empty($lastname) || empty($email) || empty($msg)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// Validate email format
if (!validate_email($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

// Validate phone number if provided
if (!empty($telno) && !validate_phone($telno)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
    exit;
}

// Validate message length
if (!validate_message($msg)) {
    echo json_encode(['success' => false, 'message' => 'Message must be at least 6 characters long.']);
    exit;
}

// Validate Turnstile CAPTCHA
$turnstile_secret = TURNSTILE_SECRET; // Replace with your actual secret key
$captcha_verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

$captcha_data = [
    'secret' => $turnstile_secret,
    'response' => $captcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$ssl_options = [];

if (strpos($_SERVER['SERVER_NAME'], '.local') !== false) {
    $ssl_options = [
        "ssl" => [
            "cafile" => "/etc/ssl/cert.pem",
            "verify_peer" => true,
            "verify_peer_name" => true,
        ]
    ];
}

$options = array_merge([
    "http" => [
        "header"  => "Content-Type: application/x-www-form-urlencoded\r\n",
        "method"  => "POST",
        "content" => http_build_query($captcha_data)
    ]
], $ssl_options);

$context = stream_context_create($options);
$captcha_result = json_decode(file_get_contents($captcha_verify_url, false, $context), true);

if (!$captcha_result['success']) {
    echo json_encode(['success' => false, 'message' => 'Captcha verification failed.']);
    exit;
}

// Sender details
$emailData = [
    'from' => [
        'full_name'     => $firstname . ' ' . $lastname,
        'firstname'     => $firstname,
        'lastname'      => $lastname,
        'email'         => $email,
        'telno'         => $telno
    ],
    'to' => [
        'name'      => SEND_NAME,
        'email'     => SEND_TO
    ]
];

$emailSubject = "New Enquiry from {$emailData['from']['full_name']}";
$replySubject = "Thanks for getting in touch!";

// Generate email content in HTML
$emailBody = "<p>{$msg}</p>";

// Format the email using write_email function
$fullEmail = write_email($emailData, $emailBody, 'contact'); // To hermine
$fullReply = write_email($emailData, $emailBody, 'reply'); // Autoresponder

// Send the email
send_email($emailData, $emailSubject, $fullEmail, false, false); // To Hermine
send_email($emailData, $replySubject, $fullReply, true, false); // Autoresponder

echo json_encode(['success' => true, 'message' => 'Your enquiry has been submitted.']);
exit;

?>