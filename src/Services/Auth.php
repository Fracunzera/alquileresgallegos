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

    /** La sesión dura 30 días (anunciantes); la del admin vence a las 12 h sin actividad. */
    private const DURACION = 30 * 86400;
    private const INACTIVIDAD_ADMIN = 12 * 3600;

    /** Hash bcrypt válido de una clave descartada: iguala el tiempo de respuesta cuando el email no existe. */
    private const HASH_FALSO = '$2y$10$Ws1DwCYJGPQcSj3VDlCTLOgTeV5SPi9Bma.m5rzik3ITla5PgEqdy';

    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        // Carpeta propia: en muchos servidores un cron del sistema borra /var/lib/php/sessions
        // con su propio vencimiento e ignora gc_maxlifetime.
        $carpeta = RAIZ . '/storage/sessions';
        if (is_dir($carpeta) || @mkdir($carpeta, 0700, true)) {
            session_save_path($carpeta);
        }
        ini_set('session.gc_maxlifetime', (string) self::DURACION);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name(self::COOKIE);
        session_set_cookie_params([
            'lifetime' => self::DURACION,
            'path' => '/',
            'secure' => \App\Seguridad::sitioHttps() || (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function estaLogueado(): bool
    {
        if (!isset($_COOKIE[self::COOKIE]) && session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        self::iniciarSesion();
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        if (time() - (int) ($_SESSION['admin_actividad'] ?? 0) > self::INACTIVIDAD_ADMIN) {
            unset($_SESSION['admin_id'], $_SESSION['admin_nombre'], $_SESSION['admin_actividad']);
            return false;
        }
        $_SESSION['admin_actividad'] = time();
        return true;
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
        $hash = $admin['password_hash'] ?? self::HASH_FALSO;
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
        $_SESSION['admin_actividad'] = time();
        Db::run('UPDATE admins SET ultimo_login = NOW() WHERE id = ?', [$admin['id']]);
        return true;
    }

    public static function logout(): void
    {
        self::iniciarSesion();
        unset($_SESSION['admin_id'], $_SESSION['admin_nombre'], $_SESSION['admin_actividad']);
        session_regenerate_id(true);
    }
}
