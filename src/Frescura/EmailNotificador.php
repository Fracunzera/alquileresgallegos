<?php

declare(strict_types=1);

namespace App\Frescura;

use App\Mail\Email;
use App\Mail\Mail;

/** Envía el pedido de confirmación por email (Brevo en producción). */
final class EmailNotificador implements Notificador
{
    public function enviar(array $aviso, string $link, string $mensaje): bool
    {
        if (empty($aviso['contacto_email'])) {
            return false;
        }
        return Mail::enviar(new Email($aviso['contacto_email'], '¿Tu aviso sigue disponible? ' . $aviso['titulo'], $mensaje));
    }
}
