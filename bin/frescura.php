<?php

declare(strict_types=1);

/*
 * Cron diario de frescura de avisos.
 *  - Informa cuántos avisos hay en la cola de verificación.
 *  - Con --solicitar, manda el "¿ya lo alquilaste o sigue disponible?" a los que todavía no lo
 *    recibieron (con NOTIFICADOR=email; los avisos sin email quedan en la cola del panel para
 *    mandarlos a mano por WhatsApp) y un recordatorio FRESCURA_DIAS_RECORDATORIO días antes de pausar.
 *  - Si FRESCURA_AUTODESPUBLICAR=1, pausa los que no respondieron en FRESCURA_DIAS_GRACIA días
 *    y les avisa que lo pueden volver a publicar.
 *
 * Uso: php bin/frescura.php [--solicitar]
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Config;
use App\Frescura\ColaVerificacion;

$fecha = date('Y-m-d H:i');
$pendientes = ColaVerificacion::pendientes();
echo "[{$fecha}] En cola de verificación: " . count($pendientes) . "\n";

if (in_array('--solicitar', $argv, true)) {
    $notificador = ColaVerificacion::notificador();
    $enviados = 0;
    foreach ($pendientes as $aviso) {
        if ($aviso['verificacion_solicitada_en'] !== null || !$notificador->puedeEnviar($aviso)) {
            continue;
        }
        if (ColaVerificacion::solicitar((int) $aviso['id'])['enviado']) {
            $enviados++;
        } else {
            ColaVerificacion::anularSolicitud((int) $aviso['id']); // falló el envío: se reintenta mañana
        }
    }
    echo "[{$fecha}] Pedidos de confirmación enviados: {$enviados}\n";
    echo "[{$fecha}] Recordatorios enviados: " . count(ColaVerificacion::recordarPorVencer()) . "\n";
}

$borrados = App\Services\Confirmacion::limpiarVencidos();
echo "[{$fecha}] Avisos sin confirmar borrados: {$borrados}\n";

if (Config::bool('FRESCURA_AUTODESPUBLICAR')) {
    $pausados = ColaVerificacion::despublicarSinRespuesta();
    echo "[{$fecha}] Pausados por falta de respuesta: " . count($pausados) . ($pausados ? ' (n.º ' . implode(', ', $pausados) . ')' : '') . "\n";
}
