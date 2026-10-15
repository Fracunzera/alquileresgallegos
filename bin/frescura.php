<?php

declare(strict_types=1);

/*
 * Cron diario de frescura de avisos.
 *  - Informa cuántos avisos hay en la cola de verificación.
 *  - Si FRESCURA_AUTODESPUBLICAR=1, pausa los que no respondieron al pedido en FRESCURA_DIAS_GRACIA días.
 *  - Con --solicitar, además genera y envía el pedido a los que todavía no lo tienen
 *    (solo tiene sentido con NOTIFICADOR=email|whatsapp; con null no se envía nada).
 *
 * Uso: php bin/frescura.php [--solicitar]
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Config;
use App\Frescura\ColaVerificacion;

$fecha = date('Y-m-d H:i');
$pendientes = ColaVerificacion::pendientes();
echo "[{$fecha}] En cola de verificación: " . count($pendientes) . "\n";

if (in_array('--solicitar', $argv, true) && Config::get('NOTIFICADOR', 'null') !== 'null') {
    $enviados = 0;
    foreach ($pendientes as $aviso) {
        if ($aviso['verificacion_solicitada_en'] === null && ColaVerificacion::solicitar((int) $aviso['id'])['enviado']) {
            $enviados++;
        }
    }
    echo "[{$fecha}] Pedidos de confirmación enviados: {$enviados}\n";
}

if (Config::bool('FRESCURA_AUTODESPUBLICAR')) {
    $pausados = ColaVerificacion::despublicarSinRespuesta();
    echo "[{$fecha}] Pausados por falta de respuesta: " . count($pausados) . ($pausados ? ' (n.º ' . implode(', ', $pausados) . ')' : '') . "\n";
}
