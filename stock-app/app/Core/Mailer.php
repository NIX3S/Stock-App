<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * Envoi d'e-mails via PHPMailer + SMTP.
 *
 * Configuration dans .env :
 *   MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS, MAIL_FROM, MAIL_FROM_NAME
 *
 * En cas d'échec (SMTP inaccessible, mauvais credentials…) :
 *   - L'exception PHPMailer est capturée et loggée dans storage/logs/mail.log
 *   - La méthode retourne le lien/corps du message pour que le contrôleur
 *     puisse l'afficher directement (utile en dev ou si SMTP indisponible)
 */
final class Mailer
{
    /**
     * Envoie un e-mail HTML.
     *
     * @return string|null  null = envoi réussi
     *                      string = corps HTML si l'envoi a échoué (pour affichage direct)
     */
    public static function send(string $to, string $subject, string $htmlBody): ?string
    {
        $config = Config::all();
        $mail   = new PHPMailer(true); // true = exceptions activées

        try {
            // ── Serveur SMTP ──────────────────────────────────────────────
            $mail->isSMTP();
            $mail->Host       = $config['mail']['host']      ?? 'localhost';
            $mail->Port       = (int) ($config['mail']['port'] ?? 587);
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['mail']['user']      ?? '';
            $mail->Password   = $config['mail']['pass']      ?? '';
            $mail->SMTPSecure = ((int)($config['mail']['port'] ?? 587) === 465)
                                ? PHPMailer::ENCRYPTION_SMTPS
                                : PHPMailer::ENCRYPTION_STARTTLS;

            // Debug SMTP dans les logs PHP (0 = off, 2 = complet)
            $mail->SMTPDebug  = SMTP::DEBUG_OFF;
            $mail->Debugoutput = function (string $str, int $level): void {
                error_log("[PHPMailer SMTP] {$str}");
            };

            // ── Expéditeur / destinataire ─────────────────────────────────
            $mail->setFrom(
                $config['mail']['from']      ?? 'no-reply@localhost',
                $config['mail']['from_name'] ?? 'Application'
            );
            $mail->addAddress($to);

            // ── Contenu ───────────────────────────────────────────────────
            $mail->CharSet  = PHPMailer::CHARSET_UTF8;
            $mail->isHTML(true);
            $mail->Subject  = $subject;
            $mail->Body     = $htmlBody;
            $mail->AltBody  = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], "\n", $htmlBody));

            $mail->send();

            // Succès
            self::log("ENVOYÉ à {$to} | Sujet : {$subject}");
            return null;

        } catch (MailerException $e) {
            $error = $mail->ErrorInfo;
            self::log("ÉCHEC à {$to} | Sujet : {$subject} | Erreur : {$error}", $htmlBody);
            error_log("[Mailer] SMTP error pour {$to}: {$error}");
            return $htmlBody; // Le contrôleur peut afficher le lien directement
        }
    }

    /**
     * Log dans storage/logs/mail.log (succès et échecs).
     */
    private static function log(string $summary, ?string $body = null): void
    {
        $logDir  = dirname(__DIR__, 2) . '/storage/logs';
        $logFile = $logDir . '/mail.log';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $sep   = str_repeat('─', 80);
        $entry = "[" . date('Y-m-d H:i:s') . "] {$summary}\n";
        if ($body !== null) {
            $entry .= "CORPS :\n{$body}\n";
        }
        $entry .= "{$sep}\n";

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
