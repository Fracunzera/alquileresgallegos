<?php

declare(strict_types=1);

namespace App\Frescura;

/**
 * Hook para pedirle al anunciante que confirme si el aviso sigue disponible.
 * Implementaciones: NullNotificador (manual), EmailNotificador, WhatsappNotificador (stub).
 */
interface Notificador
{
    /**
     * true si este canal puede llegarle al anunciante solo (el cron solo le pide confirmación a esos:
     * pausar un aviso por "no responder" un mensaje que nunca se mandó no tendría sentido).
     *
     * @param array<string, mixed> $aviso fila de avisos
     */
    public function puedeEnviar(array $aviso): bool;

    /**
     * @param array<string, mixed> $aviso fila de avisos
     * @param string $link URL absoluta para confirmar con un clic
     * @return bool true si se envió automáticamente; false si el admin lo tiene que mandar a mano
     */
    public function enviar(array $aviso, string $link, string $mensaje): bool;
}
