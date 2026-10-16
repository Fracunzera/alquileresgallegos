<?php

declare(strict_types=1);

namespace App\Frescura;

use App\Mail\Mail;

/** Envía el pedido de confirmación por email (Brevo en producción), con botones "Sigue disponible" / "Ya lo alquilé". */
final class EmailNotificador implements Notificador
{
    public function puedeEnviar(array $aviso): bool
    {
        return ColaVerificacion::destinatario($aviso) !== null;
    }

    public function enviar(array $aviso, string $link, string $mensaje): bool
    {
        $para = ColaVerificacion::destinatario($aviso);
        return $para !== null && Mail::enviar(ColaVerificacion::emailPedido($aviso, $para, $link));
    }
}
