<?php
/**
 * Send Email
 */

// Sanitize input function
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Validate email function
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (allows digits, spaces, dashes, parentheses, and plus sign)
function validate_phone($phone) {
    return preg_match('/^\+?[0-9 \-()]+$/', $phone);
}

// Validate message length
function validate_message($message) {
    return strlen($message) > 5;
}

define('SEND_MAIL', 'sendmail');
define('SEND_FROM', 'no-reply@thebardsgarage.dev');
define('SEND_TO', 'nathan@thewpbard.dev');
define('SEND_NAME', 'The Cosy Crit');

define('TURNSTILE_SECRET', '0x4AAAAAABHebtGzn5oH8oX4kTbuhAyp9Ms');

/**
 * Sends the email using Brevo (formerly Sendinblue) via cURL or PHP mail() as a fallback.
 */
function send_email($emailData, $subject, $email, $reply = false, $echo = true) {
    if ( $reply === false ) { // If is submission
        $fromName = $emailData['from']['full_name'];
        $toEmail = $emailData['to']['email'];
        $toName = $emailData['to']['name'];
        $replyEmail = $emailData['from']['email'];
        $replyName = $emailData['from']['full_name'];
    } else { // If is reply   
        $fromName = $emailData['to']['name'];
        $toEmail = $emailData['from']['email'];
        $toName = $emailData['from']['full_name']; 
        $replyEmail = $emailData['to']['email'];
        $replyName = $emailData['to']['name'];
    }
    $fromEmail = SEND_FROM;

    if (SEND_MAIL === 'brevo') {
        // Set endpoint and API key
        $endpoint = 'https://api.brevo.com/v3/smtp/email';

        // Request payload
        $brevoData = array(
            'sender' => array(
                'name' => $fromName,
                'email' => $fromEmail
            ),
            'to' => array(
                array(
                    'email' => $toEmail,
                    'name' => $toName
                )
            ),
            'replyTo' => array(
                'name' => $replyName,
                'email' => $replyEmail
            ),
            'subject' => $subject ?? "Email from The Cosy Crit",
            'htmlContent' => $email
        );

        // Set cURL options
        $options = [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($brevoData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'accept: application/json',
                'api-key: ' . BREVO_API,
                'content-type: application/json'
            )
        ];

        // Disable SSL verification for local development
        if (strpos($_SERVER['SERVER_NAME'], '.local') !== false) {
            $options += [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ];
        } else {
            // Default SSL settings (enabled for production)
            $options += [
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ];
        }

        // Initialize and execute cURL request
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);

        // Handle errors
        if ($response === false) {
            echo 'Error: ' . curl_error($curl);
        } else {
            $response_data = json_decode($response, true);
            if (isset($response_data['messageId'])) {
                if ( $echo === true ){
                    http_response_code(200);
                    echo json_encode(['success' => true, 'message' => 'The request was successful']);
                } else {
                    return true;
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'There was an error processing the request']);
            }
        }

        // Close cURL session
        curl_close($curl);
    } else {
        // Fallback to PHP mail()
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>" . "\r\n";
        $headers .= "Reply-To: {$replyEmail}" . "\r\n";

        $sendmail = mail($toEmail, $subject, $email, $headers);

        if ( $sendmail ){
            if ( $echo === true ){
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'The request was successful']);
            } else {
                return true;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'There was an error processing the request']);
        }
    }
}

?>