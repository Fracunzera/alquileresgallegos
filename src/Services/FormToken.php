<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;

/**
 * Token "timestamp.firma" para formularios públicos sin sesión: prueba que el formulario salió
 * de este sitio y que no se completó en un instante (los bots lo envían en milisegundos).
 */
final class FormToken
{
    public static function generar(string $proposito, ?int $momento = null): string
    {
        $momento ??= time();
        return $momento . '.' . self::firma($proposito, $momento);
    }

    public static function valido(string $token, string $proposito, int $minimoSegundos, int $maximoSegundos): bool
    {
        [$momento, $firma] = array_pad(explode('.', $token, 2), 2, '');
        if (!ctype_digit($momento) || !hash_equals(self::firma($proposito, (int) $momento), $firma)) {
            return false;
        }
        $edad = time() - (int) $momento;
        return $edad >= $minimoSegundos && $edad <= $maximoSegundos;
    }

    private static function firma(string $proposito, int $momento): string
    {
        return hash_hmac('sha256', $proposito . '|' . $momento, Config::clave());
    }
}
