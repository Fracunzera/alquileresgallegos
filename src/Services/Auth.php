<?php

declare(strict_types=1);

namespace App\Services;

use App\Db;
use App\Http;

/**
 * Sesión del panel de administración. La cookie de sesión es compartida con las cuentas de
 * anunciantes (CuentaAuth): cada uno guarda sus propias claves y al salir borra solo las suyas.
 * Solo se inicia si ya existe la cookie (o en un login), así las páginas públicas no mandan
 * Set-Cookie y se pueden cachear.
 */
final class Auth
{
    public const COOKIE = 'alq_sesion';

    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name(self::COOKIE);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public static function estaLogueado(): bool
    {
        if (!isset($_COOKIE[self::COOKIE]) && session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        self::iniciarSesion();
        return isset($_SESSION['admin_id']);
    }

    public static function adminId(): ?int
    {
        return self::estaLogueado() ? (int) $_SESSION['admin_id'] : null;
    }

    public static function adminNombre(): string
    {
        return self::estaLogueado() ? (string) ($_SESSION['admin_nombre'] ?? '') : '';
    }

    public static function requerir(): void
    {
        if (!self::estaLogueado()) {
            Http::redirigir('/admin/login');
        }
    }

    public static function login(string $email, string $password): bool
    {
        $admin = Db::fila('SELECT * FROM admins WHERE email = ?', [mb_strtolower(trim($email))]);
        // Verificar contra un hash falso si no existe, para no revelar emails por timing.
        $hash = $admin['password_hash'] ?? '$2y$10$usesomesillystringforsaltOdzcvmnUnrJkMVyamJSBdeQcGkW';
        if (!password_verify($password, $hash) || $admin === null) {
            return false;
        }
        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            Db::run('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
        }
        self::iniciarSesion();
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        Db::run('UPDATE admins SET ultimo_login = NOW() WHERE id = ?', [$admin['id']]);
        return true;
    }

    public static function logout(): void
    {
        self::iniciarSesion();
        unset($_SESSION['admin_id'], $_SESSION['admin_nombre']);
        session_regenerate_id(true);
    }
}
