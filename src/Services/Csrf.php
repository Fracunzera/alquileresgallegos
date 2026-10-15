<?php

declare(strict_types=1);

namespace App\Services;

/** Token CSRF por sesión para el panel. */
final class Csrf
{
    public static function token(): string
    {
        Auth::iniciarSesion();
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function verificar(): void
    {
        Auth::iniciarSesion();
        $enviado = $_POST['_csrf'] ?? '';
        if (!is_string($enviado) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $enviado)) {
            http_response_code(419);
            exit('La sesión expiró. Volvé atrás, recargá la página y probá de nuevo.');
        }
    }
}
