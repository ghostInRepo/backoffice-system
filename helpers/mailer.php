<?php
// Mailer helper supports PHPMailer via Composer if installed.
// Falls back to writing mail to var/logs/mail.log for local development.
function mailer_send($to, $subject, $body, $isHtml = false) {
    $config = require __DIR__ . '/../config/config.php';
    $mailCfg = $config['mail'] ?? [];

    // Attempt to use PHPMailer if available and SMTP configured
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        // Use class_exists to ensure PHPMailer is available
        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mailClass = '\\PHPMailer\\PHPMailer\\PHPMailer';
                $mail = new $mailClass(true);
                if (!empty($mailCfg['smtp_host']) && !empty($mailCfg['smtp_user'])) {
                    // SMTP mode
                    $mail->isSMTP();
                    $mail->Host = $mailCfg['smtp_host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $mailCfg['smtp_user'];
                    $mail->Password = $mailCfg['smtp_pass'];
                    $mail->SMTPSecure = $mailCfg['smtp_secure'] ?? $mailClass::ENCRYPTION_STARTTLS;
                    $mail->Port = $mailCfg['smtp_port'] ?? 587;
                }
                $mail->setFrom($mailCfg['from_email'] ?? 'no-reply@example.com', $mailCfg['from_name'] ?? 'Backoffice');
                $mail->addAddress($to);
                $mail->Subject = $subject;
                if ($isHtml) {
                    $mail->isHTML(true);
                    $mail->Body = $body;
                    // strip tags for AltBody
                    $mail->AltBody = strip_tags($body);
                } else {
                    $mail->Body = $body;
                }
                $mail->send();
                return true;
            } catch (\Exception $e) {
                // fall through to log fallback
            }
        }
    }

    // Fallback: file log + PHP mail
    $logDir = __DIR__ . '/../var/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $entry = "To: $to\nSubject: $subject\n\n" . ($isHtml ? strip_tags($body) : $body) . "\n----\n";
    file_put_contents($logDir . '/mail.log', $entry, FILE_APPEND);
    
    // If content looks like an OTP (6-digit) or subject mentions OTP, write per-email OTP file for tests
    $plain = $isHtml ? strip_tags($body) : $body;
    $otp = null;
    if (preg_match('/\b(\d{6})\b/', $plain, $m)) {
        $otp = $m[1];
    }
    if ($otp || stripos($subject, 'otp') !== false || stripos($plain, 'otp') !== false) {
        $san = preg_replace('/[^a-z0-9._-]/', '_', strtolower($to));
        $otpRecord = [
            'to' => $to,
            'subject' => $subject,
            'otp' => $otp,
            'body' => substr($plain, 0, 1024),
            'timestamp' => time()
        ];
        @file_put_contents($logDir . "/otp_{$san}.json", json_encode($otpRecord, JSON_PRETTY_PRINT));
    }
    if (function_exists('mail')) @mail($to, $subject, $body);
    return true;
}
