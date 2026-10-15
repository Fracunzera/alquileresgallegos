<?php

declare(strict_types=1);

namespace App\Frescura;

/**
 * Stub para la API de WhatsApp Business (Cloud API de Meta).
 * Requiere: número verificado, plantilla de mensaje aprobada y token (WHATSAPP_TOKEN, WHATSAPP_PHONE_ID).
 * Mientras no esté implementado devuelve false y el admin manda el mensaje a mano desde el panel.
 */
final class WhatsappNotificador implements Notificador
{
    public function enviar(array $aviso, string $link, string $mensaje): bool
    {
        // TODO: POST https://graph.facebook.com/v{N}/{WHATSAPP_PHONE_ID}/messages con la plantilla aprobada.
        return false;
    }
}
