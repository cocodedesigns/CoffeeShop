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

define('SEND_MAIL', 'send');
define('SEND_FROM', 'no-reply@thebardsgarage.dev');
define('SEND_TO', 'nathan@thewpbard.dev');
define('SEND_NAME', 'The Cosy Crit');

define('TURNSTILE_SECRET', '0x4AAAAAABHebtGzn5oH8oX4kTbuhAyp9Ms');

define('BREVO_API', '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');

// Define constants for HubSpot API key 
define('HUBSPOT_API_KEY', 'pat-na1-0f9558ee-ad7c-4588-8949-4954388e788b'); // Replace with your HubSpot API key

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
            'subject' => $subject ?? "Email from Re-flective",
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

function getHubSpotAccountId() {
    $endpoint = 'https://api.hubapi.com/integrations/v1/me';

    // Set cURL options for contact creation
    $options = [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . HUBSPOT_API_KEY
        ]
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

    // Initialize cURL and execute request
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Check if cURL request failed
    if ($response === false) {
        echo "cURL error: " . curl_error($curl);
        return null;
    }

    // Debug: output raw response to inspect
    // echo "Response: $response\n";

    // Handle API response
    if ($statusCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['portalId'])) {
            return $data['portalId']; // This is your HubSpot account ID (portalId)
        } else {
            echo "No portalId found in the response.\n";
        }
    } else {
        echo 'Failed to retrieve HubSpot account ID. Status Code: ' . $statusCode . "\n";
        return null;
    }
}

function sendToHubSpot($formData, $echo = true) {
    // Prepare the data to be sent to HubSpot
    $contactData = [
        'properties' => [
            'firstname'             => $formData['from']['firstname'],
            'lastname'              => $formData['from']['lastname'],
            'email'                 => $formData['from']['email'],
            'phone'                 => $formData['from']['telno'],
            'service'               => $formData['service'],
            'contact_preference'    => $formData['preference'],
            'lifecyclestage'        => 'lead'
        ]
    ];

    // Convert the data to JSON
    $jsonData = json_encode($contactData);

    // HubSpot API URL for creating a contact
    $contactEndpoint = 'https://api.hubapi.com/crm/v3/objects/contacts';

    // Set cURL options for contact creation
    $options = [
        CURLOPT_URL => $contactEndpoint,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . HUBSPOT_API_KEY
        ]
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

    // Initialize cURL and execute request
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    date_default_timezone_set('Europe/London');
    $current_timestamp = time();

    // Decode the response
    $responseData = json_decode($response, true);

    if ($statusCode == 201 && isset($responseData['id'])) {
        $contactId = $responseData['id']; // Get HubSpot Contact ID

        // Now create a note
        $noteData = [
            'properties' => [
                'hs_note_body' => $formData['details'], // The message as the note content
                'hs_timestamp' => $current_timestamp // Current timestamp in milliseconds
            ]
        ];

        // Convert note data to JSON
        $jsonNoteData = json_encode($noteData);

        // HubSpot API URL for creating a note
        $noteEndpoint = 'https://api.hubapi.com/crm/v3/objects/notes';

        // Set cURL options for note creation
        $options[CURLOPT_URL] = $noteEndpoint;
        $options[CURLOPT_POSTFIELDS] = $jsonNoteData;

        // Initialize and execute cURL request for note
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $noteResponse = curl_exec($curl);
        $noteStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($noteStatusCode == 201) {
            $noteResponseData = json_decode($noteResponse, true);
            $noteId = $noteResponseData['id']; // Get the note ID

            // Now associate the note with the contact using PUT method
            $associationEndpoint = 'https://api.hubapi.com/crm/v4/objects/notes/' . $noteId . '/associations/default/contacts/' . $contactId;

            // Set cURL options for association request using PUT
            $options[CURLOPT_URL] = $associationEndpoint;
            $options[CURLOPT_CUSTOMREQUEST] = 'PUT'; // Using PUT method instead of POST
            $options[CURLOPT_POSTFIELDS] = ''; // No data needed for the association
            $options[CURLOPT_RETURNTRANSFER] = true;
            $options[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . HUBSPOT_API_KEY
            ];

            // Initialize and execute cURL request for association
            $curl = curl_init();
            curl_setopt_array($curl, $options);
            $associationResponse = curl_exec($curl);
            $associationStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($associationStatusCode == 200 || $associationStatusCode == 201) {
                if ( $echo === true ){
                    http_response_code(200);
                    echo json_encode(['success' => true, 'message' => 'Form, note, and association successfully saved in HubSpot!']);
                } else {
                    return true;
                }
            } else {
                http_response_code($associationStatusCode);
                echo json_encode(['success' => true, 'message' => 'Form and note saved, but association failed. <br>Response: ' . $associationResponse]);
            }
        } else {
            http_response_code($noteStatusCode);
            echo json_encode(['success' => true, 'message' => 'Form submitted but failed to save note.']);
        }
    } else {
        http_response_code($statusCode);
        echo json_encode(['success' => true, 'message' => 'Failed to submit form to HubSpot.']);
    }
}

?>