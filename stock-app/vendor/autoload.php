<?php
/**
 * Autoloader minimaliste pour PHPMailer (sans Composer sur l'hébergement).
 * Si vous utilisez Composer, remplacez ce fichier par require vendor/autoload.php
 * généré par Composer.
 */

spl_autoload_register(function (string $class): void {
    $map = [
        'PHPMailer\\PHPMailer\\PHPMailer'  => __DIR__ . '/phpmailer/phpmailer/PHPMailer.php',
        'PHPMailer\\PHPMailer\\SMTP'       => __DIR__ . '/phpmailer/phpmailer/SMTP.php',
        'PHPMailer\\PHPMailer\\Exception'  => __DIR__ . '/phpmailer/phpmailer/Exception.php',
        'PHPMailer\\PHPMailer\\OAuth'      => __DIR__ . '/phpmailer/phpmailer/OAuth.php',
        'PHPMailer\\PHPMailer\\POP3'       => __DIR__ . '/phpmailer/phpmailer/POP3.php',
    ];
    if (isset($map[$class])) {
        require_once $map[$class];
    }
});
