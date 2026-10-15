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

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function redirigir(string $url, int $codigo = 302): never
    {
        header('Location: ' . $url, true, $codigo);
        exit;
    }
}
