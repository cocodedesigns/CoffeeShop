<?php

function write_email($emailData, $content, $email = 'contact'){
    switch ($email){
        case 'contact':
            $salutation = "Hi!";
            $welcome = "You have received this email from <strong>{$emailData['from']['full_name']}</strong>. You can reply to this email directly.";
            break;

        case 'leadgen':
            $salutation = "Hi!";
            $welcome = "You have received this enquiry from <strong>{$emailData['from']['full_name']}</strong>. You can reply to this email directly. Their information has also been added to your CRM, with their permission.";
            break;

        case 'reply':
            $salutation = "Hi {$emailData['from']['firstname']}!";
            $welcome = "Thanks for getting in touch with us. We have received your email and will respond shortly. A copy of your email can be found below:";
            break;

        case 'reply_leadgen':
            $salutation = "Hi {$emailData['from']['firstname']}!";
            $welcome = "Thanks for enquiring about our services. We have received your submission and will respond shortly. As agreed, we have stored a copy in our customer database. A copy of your submission can be found below:";
            break;

    }

    $email = "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>The Cosy Crit</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                p { padding: 10px 0; margin: 0; }
                a{ color: #e01a4f; }
                a:hover{ color: #3463FF; }
                .email-container { max-width: 1000px; margin: 0 auto; background-color: #ffffff; border: solid 1px #AFB7C1; }
                .email-header { padding: 30px; text-align: center; }
                .email-header img { width: 175px; }
                .email-body { padding: 30px; color: #333333; }
                .email-body p { font-size: 16px; line-height: 1.5; }
                .email-footer { text-align: center; padding: 15px; font-size: 14px; color: #777; }
                .email-content { margin: 20px 0; padding: 20px; border: solid 1px #a1aab6; background-color: #f4f4f4; color: #444444; }
                .sender-info { margin: 25px 0; padding: 20px; border: solid 1px #a1aab6; background-color: #f4f4f4; color: #777777; }
                .sender-info p.heading { font-size: 11px; text-transform: uppercase; }
                .sender-info p{ font-size: 14px; padding: 6px 0; }
                .email-divider { border: none; max-width: 450px; height: 1px; background-color: #dae0fc; margin: 36px auto; }
            </style>
        </head>
        <body>
            <div class='email-header'>
                <img src='https://thecosycrit-plus.thebardsgarage.dev/images/email-logo.png' alt='The Cosy Crit'>
            </div>
            <div class='email-container'>
                <div class='email-body'>
                    <p>{$salutation}</p>
                    <p>{$welcome}</p>
                    <div class='email-content'>
                        {$content}
                    </div>
                    <hr class='email-divider' />
                    <div class='sender-info'>
                        <p class='heading'>Sender information</p>
                        <p>Name: <strong>{$emailData['from']['firstname']} {$emailData['from']['lastname']}</strong></p>
                        <p>Email: <strong><a href='email:{$emailData['from']['email']}'>{$emailData['from']['email']}</a></strong></p>
                        " . (!empty($emailData['from']['telno']) ? "<p>Phone: <strong>{$emailData['from']['telno']}</strong></p>" : "") . "
                    </div>
                </div>
            </div>
            <div class='email-footer'>
                <p>&copy; 2025 The Cosy Crit. All rights reserved.</p>
            </div>
        </body>
        </html>
    ";

    return $email;
}

?>