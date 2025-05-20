<?php

namespace Src\Utilities;

use PHPMailer\PHPMailer\PHPMailer;
use Src\Utilities\Validator;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private const DEFAULT_CHARSET = 'UTF-8';
    private const DEFAULT_TIMEOUT = 15;

    private static function getSmtpConfig()
    {
        return [
            'host' => $_ENV['SMTP_HOST'] ?? 'mail.unifyze.cloudet.co',
            'username' => $_ENV['SMTP_USERNAME'] ?? '',
            'password' => $_ENV['SMTP_PASSWORD'] ?? '',
            'port' => $_ENV['SMTP_PORT'] ?? 465,
            'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_SMTPS,
            'from_email' => $_ENV['SMTP_FROM_ADDRESS'] ?? $_ENV['SMTP_USERNAME'] ?? '',
            'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'Unifyze',
        ];
    }

    /**
     * Send an email with proper error handling and security
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body content (HTML)
     * @param string $name Recipient name (optional)
     * @param array $data Additional template data (if using templates)
     * @param string|null $type Email type (for logging/templating)
     * @return bool True on success, false on failure
     * @throws \RuntimeException If email sending fails
     */
    public static function sendEmail(string $to, string $subject, string $body = '', string $name = '', array $data = [], ?string $type = null): bool
    {

        if (!Validator::email($to)) {
            error_log("Invalid email address format: " . htmlspecialchars($to, ENT_QUOTES, 'UTF-8'));
            return false;
        }
        $to = Validator::sanitizeEmail($to);

        $mail = new PHPMailer(true);

        try {
            // Server settings 
            $smtp = self::getSmtpConfig();
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['username'];
            $mail->Password   = $smtp['password'];
            $mail->SMTPSecure = $smtp['encryption'];
            $mail->Port       = $smtp['port'];
            $mail->CharSet    = self::DEFAULT_CHARSET;
            $mail->Timeout    = self::DEFAULT_TIMEOUT;
            $mail->SMTPKeepAlive = false;

            // Enable verbose error output in debug mode
            if (getenv('APP_DEBUG') === 'true') {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function ($str, $level) {
                    error_log("SMTP Debug: $str");
                };
            }

            // Set charset
            $mail->CharSet = self::DEFAULT_CHARSET;

            // Recipients
            $mail->setFrom(
                $smtp['from_email'],
                $smtp['from_name']
            );
            $mail->addReplyTo(
                $smtp['from_email'],
                $smtp['from_name']
            );
            $mail->addAddress($to, $name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            if ($type) {
                $mail->Body = self::buildBody($type, array_merge(['name' => $name], (array)$data));
            } else {
                $mail->Body = $body;
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private static function buildBody($type, $params)
    {
        switch ($type) {
            case 'reset_code':
                $name = htmlspecialchars($params['name'] ?? '');
                $code = htmlspecialchars($params['code'] ?? '');
                return '
            <html>
                <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                </head>
                <body style="font-family: Arial, sans-serif; background: #ffffff; margin: 0; padding: 0;">
                <div
                    style="max-width: 500px; margin: 50px auto; background-color: #ffffff; border: 1px solid #e2e8f0; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="margin: 0; color:rgb(55, 58, 61); font-size: 26px; font-weight: bold;">Unifyze Verification</h1>
                    <p style="font-size: 14px; color: #718096; margin-top: 8px;">Secure your account with this verification code</p>
                    </div>

                    <!-- Content -->
                    <div style="color:rgb(41, 42, 45); line-height: 1.6;">
                    <p style="margin-bottom: 20px; color:rgb(41, 42, 45); font-size: 16px;">Hi <strong>' . $name . '</strong>,</p>
                    <p style="margin-bottom: 25px; color:rgb(41, 42, 45); font-size: 16px;">Here\'s your verification code:</p>

                    <!-- Code Box -->
                    <div
                        style="background: #edf2f7; border: 2px dashed #cbd5e0; border-radius: 10px; padding: 25px; text-align: center; margin-bottom: 30px;">
                        <span style="font-size: 32px; font-weight: bold; color: #2c5282; letter-spacing: 4px;">' . $code . '</span>
                    </div>

                    <p style="margin-bottom: 25px; font-size: 14px; color: #718096;">
                        This code will expire in <strong>15 minutes</strong>. Please do not share it with anyone for security reasons.
                    </p>
                    </div>

                    <!-- Footer -->
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center;">
                    <p style="margin: 0; font-size: 13px; color: #a0aec0;">
                        If you didn\'t request this code, feel free to ignore this message.
                    </p>
                    <p style="margin-top: 15px; font-size: 13px; color: #a0aec0;">
                        &copy; ' . date('Y') . ' Unifyze Team
                    </p>
                    </div>
                </div>
                </body>
            </html>';
            default:
                return '';
        }
    }
}
