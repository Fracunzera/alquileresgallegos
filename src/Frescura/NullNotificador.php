<?php

declare(strict_types=1);

namespace App\Frescura;

/** No envía nada: el panel muestra el link de WhatsApp con el mensaje armado para mandarlo a mano. */
final class NullNotificador implements Notificador
{
    public function enviar(array $aviso, string $link, string $mensaje): bool
    {
        return false;
    }
}
