<?php

declare(strict_types=1);

namespace App\Frescura;

use App\Config;

/**
 * Envía el pedido por email con mail() de PHP (disponible en casi todo hosting compartido).
 * Para volumen, reemplazar por SMTP/servicio transaccional implementando la misma interfaz.
 */
final class EmailNotificador implements Notificador
{
    public function enviar(array $aviso, string $link, string $mensaje): bool
    {
        if (empty($aviso['contacto_email'])) {
            return false;
        }
        $de = (string) Config::get('APP_EMAIL_CONTACTO', 'no-responder@localhost');
        $asunto = '=?UTF-8?B?' . base64_encode('¿Tu aviso sigue disponible? ' . $aviso['titulo']) . '?=';
        $cabeceras = implode("\r\n", [
            'From: ' . app_nombre() . " <{$de}>",
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ]);
        return mail($aviso['contacto_email'], $asunto, $mensaje, $cabeceras);
    }
}
