<?php

declare(strict_types=1);

namespace App\Services;

use App\Db;

/**
 * Visitas y consultas (clics en WhatsApp, teléfono y email) por aviso y por día.
 * Son aproximadas: se cuentan desde el navegador y se descartan bots y vistas previas.
 */
final class Estadisticas
{
    /** evento recibido => columna */
    public const EVENTOS = ['vista' => 'vistas', 'whatsapp' => 'whatsapp', 'telefono' => 'telefono', 'email' => 'email'];

    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|headless|lighthouse|curl|wget|python|php/i';

    public static function esBot(string $userAgent): bool
    {
        return $userAgent === '' || preg_match(self::BOTS, $userAgent) === 1;
    }

    public static function registrar(int $avisoId, string $evento): void
    {
        $columna = self::EVENTOS[$evento] ?? throw new \InvalidArgumentException("Evento inválido: {$evento}");
        Db::run(
            "INSERT INTO aviso_estadisticas (aviso_id, fecha, {$columna}) VALUES (?, CURDATE(), 1)
             ON DUPLICATE KEY UPDATE {$columna} = {$columna} + 1",
            [$avisoId]
        );
    }

    /**
     * Totales de los últimos $dias días por aviso.
     *
     * @param list<int> $avisoIds
     * @return array<int, array{vistas: int, whatsapp: int, telefono: int, email: int, consultas: int}>
     */
    public static function resumen(array $avisoIds, int $dias = 30): array
    {
        $resultado = [];
        foreach ($avisoIds as $id) {
            $resultado[$id] = ['vistas' => 0, 'whatsapp' => 0, 'telefono' => 0, 'email' => 0, 'consultas' => 0];
        }
        if (!$avisoIds) {
            return $resultado;
        }
        $marcadores = implode(', ', array_fill(0, count($avisoIds), '?'));
        foreach (Db::filas(
            "SELECT aviso_id, SUM(vistas) AS vistas, SUM(whatsapp) AS whatsapp, SUM(telefono) AS telefono, SUM(email) AS email
             FROM aviso_estadisticas WHERE aviso_id IN ({$marcadores}) AND fecha > CURDATE() - INTERVAL ? DAY
             GROUP BY aviso_id",
            [...$avisoIds, $dias]
        ) as $fila) {
            $r = ['vistas' => (int) $fila['vistas'], 'whatsapp' => (int) $fila['whatsapp'], 'telefono' => (int) $fila['telefono'], 'email' => (int) $fila['email']];
            $r['consultas'] = $r['whatsapp'] + $r['telefono'] + $r['email'];
            $resultado[(int) $fila['aviso_id']] = $r;
        }
        return $resultado;
    }
}
