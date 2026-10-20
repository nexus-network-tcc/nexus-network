<?php

require_once APP_PATH . '/lib/PHPMailer/Exception.php';
require_once APP_PATH . '/lib/PHPMailer/PHPMailer.php';
require_once APP_PATH . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    /**
     * Envia um e-mail. Com MAIL_DRIVER=log, apenas grava em storage/logs/mail.log.
     */
    public static function send(string $to, string $subject, string $html, ?string $replyTo = null): bool
    {
        if (Config::get('mail.driver') !== 'smtp') {
            $entry = sprintf(
                "[%s]\nPara: %s\nAssunto: %s\n%s\n%s\n\n",
                date('Y-m-d H:i:s'),
                $to,
                $subject,
                trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], "\n", $html)))),
                str_repeat('-', 60)
            );
            return file_put_contents(ROOT_PATH . '/storage/logs/mail.log', $entry, FILE_APPEND) !== false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = Config::get('mail.host');
            $mail->Port = Config::get('mail.port');
            $mail->SMTPAuth = true;
            $mail->Username = Config::get('mail.user');
            $mail->Password = Config::get('mail.pass');
            $mail->SMTPSecure = Config::get('mail.port') === 465
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            if (!Config::get('ssl.verify')) {
                $mail->SMTPOptions = ['ssl' => [
                    'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true,
                ]];
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(Config::get('mail.from'), Config::get('mail.from_name'));
            $mail->addAddress($to);
            if ($replyTo) {
                $mail->addReplyTo($replyTo);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::wrap($subject, $html);
            $mail->AltBody = trim(strip_tags($html));
            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('Falha ao enviar e-mail: ' . $mail->ErrorInfo);
            return false;
        }
    }

    private static function wrap(string $title, string $body): string
    {
        return '<div style="background:#000;padding:32px;font-family:Arial,sans-serif;color:#fff">'
            . '<div style="max-width:520px;margin:0 auto;background:#0a0a0a;border:1px solid rgba(32,205,141,.3);border-radius:16px;padding:32px">'
            . '<h1 style="color:#20cd8d;font-size:20px;margin:0 0 16px">Nexus Network</h1>'
            . '<h2 style="font-size:18px;margin:0 0 16px;color:#fff">' . e($title) . '</h2>'
            . '<div style="color:#ccc;line-height:1.6">' . $body . '</div>'
            . '</div></div>';
    }
}
