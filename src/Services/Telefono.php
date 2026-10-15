<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Normaliza teléfonos argentinos al formato que espera wa.me (549 + área + número, sin 0 ni 15).
 * La gente los escribe de mil formas: "2966 15-123456", "(02966) 423456", "+54 9 2966 123456".
 */
final class Telefono
{
    private const AREA_POR_DEFECTO = '2966'; // Río Gallegos

    public static function whatsapp(?string $entrada): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $entrada);
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
        }
        if (str_starts_with($d, '54')) {
            $d = substr($d, 2);
            if (str_starts_with($d, '9')) {
                $d = substr($d, 1);
            }
        }
        $d = ltrim($d, '0');

        // Número local sin característica: "15123456" o "123456" / "4231234".
        if (strlen($d) <= 8) {
            if (strlen($d) === 8 && str_starts_with($d, '15')) {
                $d = substr($d, 2);
            }
            $d = self::AREA_POR_DEFECTO . $d;
        }

        // Área + 15 + número: 12 dígitos. Probar áreas de 4, 3 y 2 dígitos.
        if (strlen($d) === 12) {
            foreach ([4, 3, 2] as $largoArea) {
                if (substr($d, $largoArea, 2) === '15') {
                    $d = substr($d, 0, $largoArea) . substr($d, $largoArea + 2);
                    break;
                }
            }
        }

        return strlen($d) === 10 ? '549' . $d : null;
    }

    /** "+54 9 2966 12-3456" para mostrar. */
    public static function mostrar(string $whatsapp): string
    {
        if (preg_match('/^549(\d{4})(\d{2})(\d{4})$/', $whatsapp, $m)) {
            return "{$m[1]} {$m[2]}-{$m[3]}";
        }
        return $whatsapp;
    }
}
