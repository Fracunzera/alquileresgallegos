<?php

declare(strict_types=1);

namespace App\Mail;

/** Para desarrollo: no manda nada, escribe el email en storage/logs/mail.log. */
final class LogMailer implements Mailer
{
    public function enviar(Email $email): bool
    {
        $linea = sprintf(
            "=== %s\nPara: %s\nAsunto: %s\n\n%s\n\n",
            date('Y-m-d H:i:s'),
            $email->para,
            $email->asunto,
            $email->texto
        );
        return file_put_contents(RAIZ . '/storage/logs/mail.log', $linea, FILE_APPEND | LOCK_EX) !== false;
    }
}
