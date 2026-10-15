<?php

declare(strict_types=1);

namespace App;

final class Http
{
    public static function metodo(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : '/';
    }

    public static function query(string $clave): ?string
    {
        $valor = $_GET[$clave] ?? null;
        return is_string($valor) ? trim($valor) : null;
    }

    public static function post(string $clave): ?string
    {
        $valor = $_POST[$clave] ?? null;
        return is_string($valor) ? trim($valor) : null;
    }

    private static ?string $ip = null;

    /**
     * IP del visitante. Si la conexión viene de un proxy confiable (PROXIES_CONFIABLES, ej. Cloudflare
     * o el balanceador del hosting) se toma de la cabecera IP_CABECERA; si no, de REMOTE_ADDR.
     * Sin esto, detrás de un proxy todos los visitantes tendrían la misma IP: los límites por IP
     * serían globales y un bloqueo por IP bloquearía a todos.
     */
    public static function ip(): string
    {
        if (self::$ip !== null) {
            return self::$ip;
        }
        $remota = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rangos = array_filter(array_map('trim', explode(',', (string) Config::get('PROXIES_CONFIABLES', ''))));
        if (!$rangos || !self::ipEnRangos($remota, $rangos)) {
            return self::$ip = $remota;
        }
        $cabecera = 'HTTP_' . strtoupper(str_replace('-', '_', (string) Config::get('IP_CABECERA', 'X-Forwarded-For')));
        // "cliente, proxy1, proxy2": la primera IP desde la derecha que no sea un proxy confiable.
        foreach (array_reverse(array_map('trim', explode(',', (string) ($_SERVER[$cabecera] ?? '')))) as $candidata) {
            if (filter_var($candidata, FILTER_VALIDATE_IP) !== false && !self::ipEnRangos($candidata, $rangos)) {
                return self::$ip = $candidata;
            }
        }
        return self::$ip = $remota;
    }

    /** @param list<string> $rangos IPs o CIDR (IPv4 o IPv6) */
    public static function ipEnRangos(string $ip, array $rangos): bool
    {
        $binaria = @inet_pton($ip);
        if ($binaria === false) {
            return false;
        }
        foreach ($rangos as $rango) {
            [$red, $bits] = array_pad(explode('/', $rango, 2), 2, null);
            $redBinaria = @inet_pton($red);
            if ($redBinaria === false || strlen($redBinaria) !== strlen($binaria)) {
                continue;
            }
            $bits = $bits === null ? strlen($binaria) * 8 : (int) $bits;
            $bytes = intdiv($bits, 8);
            $resto = $bits % 8;
            if (substr($binaria, 0, $bytes) !== substr($redBinaria, 0, $bytes)) {
                continue;
            }
            if ($resto === 0) {
                return true;
            }
            $mascara = (0xFF << (8 - $resto)) & 0xFF;
            if ((ord($binaria[$bytes]) & $mascara) === (ord($redBinaria[$bytes]) & $mascara)) {
                return true;
            }
        }
        return false;
    }

    /** Solo para tests. */
    public static function reiniciarIp(): void
    {
        self::$ip = null;
    }

    public static function redirigir(string $url, int $codigo = 302): never
    {
        header('Location: ' . $url, true, $codigo);
        exit;
    }
}
