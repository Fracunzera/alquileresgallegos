<?php

declare(strict_types=1);

namespace App\Mail;

use App\Config;

/**
 * Punto único de envío. MAIL_DRIVER=brevo en producción, log en desarrollo.
 */
final class Mail
{
    private static ?Mailer $mailer = null;

    public static function enviar(Email $email): bool
    {
        try {
            return self::mailer()->enviar($email);
        } catch (\Throwable $e) {
            error_log('Mail: ' . $e->getMessage());
            return false;
        }
    }

    /** Aviso interno al equipo (moderación, denuncias). No hace nada si MODERACION_EMAIL está vacío. */
    public static function avisarModeracion(string $asunto, string $texto): void
    {
        $para = (string) Config::get('MODERACION_EMAIL', '');
        if ($para !== '') {
            self::enviar(new Email($para, '[' . app_nombre() . '] ' . $asunto, $texto));
        }
    }

    public static function mailer(): Mailer
    {
        if (self::$mailer === null) {
            $driver = (string) Config::get('MAIL_DRIVER', 'log');
            self::$mailer = $driver === 'brevo'
                ? new BrevoMailer(
                    (string) Config::get('BREVO_API_KEY', ''),
                    (string) Config::get('MAIL_FROM', ''),
                    (string) Config::get('MAIL_FROM_NOMBRE', app_nombre()),
                )
                : new LogMailer();
        }
        return self::$mailer;
    }

    /** Para tests. */
    public static function usar(Mailer $mailer): void
    {
        self::$mailer = $mailer;
    }
}
