<?php

declare(strict_types=1);

namespace App\Services;

/** Mensajes de una sola vez entre un POST y el GET siguiente (solo panel). */
final class Flash
{
    public static function ok(string $mensaje): void
    {
        Auth::iniciarSesion();
        $_SESSION['flash'][] = ['ok', $mensaje];
    }

    public static function error(string $mensaje): void
    {
        Auth::iniciarSesion();
        $_SESSION['flash'][] = ['error', $mensaje];
    }

    /** @return list<array{0: string, 1: string}> */
    public static function sacar(): array
    {
        Auth::iniciarSesion();
        $mensajes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $mensajes;
    }
}
