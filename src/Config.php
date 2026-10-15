<?php

declare(strict_types=1);

namespace App;

/**
 * Configuración leída de .env (sin dependencias). Las variables de entorno reales
 * tienen prioridad sobre el archivo, así el hosting puede sobreescribirlas.
 */
final class Config
{
    /** @var array<string, string> */
    private static array $valores = [];

    public static function cargar(string $archivo): void
    {
        self::$valores = is_file($archivo) ? self::parsear((string) file_get_contents($archivo)) : [];
    }

    /** @return array<string, string> */
    public static function parsear(string $contenido): array
    {
        $valores = [];
        foreach (preg_split('/\R/', $contenido) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            if ($valor !== '' && ($valor[0] === '"' || $valor[0] === "'")) {
                $comilla = $valor[0];
                $fin = strpos($valor, $comilla, 1);
                $valor = $fin === false ? substr($valor, 1) : substr($valor, 1, $fin - 1);
            } else {
                // Comentario al final de la línea: "VALOR   # comentario"
                $valor = trim((string) preg_replace('/\s+#.*$/', '', $valor));
            }
            $valores[$clave] = $valor;
        }
        return $valores;
    }

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        $entorno = getenv($clave);
        if ($entorno !== false) {
            return $entorno;
        }
        return self::$valores[$clave] ?? $porDefecto;
    }

    public static function int(string $clave, int $porDefecto): int
    {
        $valor = self::get($clave);
        return $valor === null || $valor === '' ? $porDefecto : (int) $valor;
    }

    public static function bool(string $clave, bool $porDefecto = false): bool
    {
        $valor = self::get($clave);
        return $valor === null || $valor === '' ? $porDefecto : in_array(strtolower($valor), ['1', 'true', 'on', 'yes'], true);
    }

    /** Fija un valor en memoria (tests). */
    public static function set(string $clave, string $valor): void
    {
        self::$valores[$clave] = $valor;
    }

    /** Clave secreta de la app (firma de tokens). */
    public static function clave(): string
    {
        $clave = (string) self::get('APP_KEY', '');
        if (strlen($clave) < 32) {
            throw new \RuntimeException('Falta APP_KEY en .env (mínimo 32 caracteres).');
        }
        return $clave;
    }
}
