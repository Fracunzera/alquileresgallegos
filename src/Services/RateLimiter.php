<?php

declare(strict_types=1);

namespace App\Services;

use App\Db;

/** Límite de intentos por clave en una ventana de tiempo (login, envíos de /publicar). */
final class RateLimiter
{
    public static function excedido(string $clave, int $maximo): bool
    {
        $intentos = Db::valor('SELECT intentos FROM rate_limits WHERE clave = ? AND ventana_fin > NOW()', [$clave]);
        return $intentos !== null && (int) $intentos >= $maximo;
    }

    public static function registrar(string $clave, int $ventanaSegundos): void
    {
        Db::run(
            'INSERT INTO rate_limits (clave, intentos, ventana_fin) VALUES (?, 1, NOW() + INTERVAL ? SECOND)
             ON DUPLICATE KEY UPDATE
               intentos = IF(ventana_fin > NOW(), intentos + 1, 1),
               ventana_fin = IF(ventana_fin > NOW(), ventana_fin, NOW() + INTERVAL ? SECOND)',
            [$clave, $ventanaSegundos, $ventanaSegundos]
        );
    }

    public static function limpiar(string $clave): void
    {
        Db::run('DELETE FROM rate_limits WHERE clave = ?', [$clave]);
    }
}
