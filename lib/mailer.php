<?php
/**
 * Global Mailer Library Wrapper using PHPMailer
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Global helper to dispatch emails using application configuration constants.
 *
 * @param string $to Email address of the recipient.
 * @param string $subject Subject line of the email.
 * @param string $body Body content of the message.
 * @param bool $isHtml Format body content as HTML. Defaults to true.
 * @return bool True on success, false on failure.
 */
function sendMail($to, $subject, $body, $isHtml = true) {
    // Basic structural validation
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("sendMail Failure: Invalid recipient email provided -> '{$to}'");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Core SMTP Server settings
        $mail->isSMTP();                                            
        $mail->Host       = SMTP_HOST;               
        $mail->SMTPAuth   = SMTP_AUTH;                                   
        $mail->Username   = SMTP_USER;      
        $mail->Password   = SMTP_PASS;                      
        $mail->Port       = SMTP_PORT;                                    
        $mail->SMTPSecure = SMTP_ENCRYPTION;    

        // Addressing
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);     

        // Content
        $mail->isHTML($isHtml);                                  
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Handle fallback text safely if content is structured as HTML
        if ($isHtml) {
            $mail->AltBody = strip_tags(str_replace(["<br>", "<br/>", "</p>"], ["\n", "\n", "\n\n"], $body));
        } else {
            $mail->AltBody = $body;
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Capture specific PHPMailer engine complaints securely inside server log files
        error_log("PHPMailer core system error occurred: {$mail->ErrorInfo}");
        return false;
    }
}
