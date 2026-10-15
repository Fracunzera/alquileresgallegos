<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use App\Db;
use App\Http;
use App\Mail\Email;
use App\Mail\Mail;

/**
 * Ingreso de anunciantes sin contraseña: se manda un link de un solo uso por email.
 * Comparte la cookie de sesión con el panel (Auth); guarda solo su propia clave.
 */
final class CuentaAuth
{
    /** @var array<string, mixed>|null|false false = todavía no se consultó */
    private static array|null|false $cuenta = false;

    public static function minutosLink(): int
    {
        return Config::int('CUENTA_LINK_MINUTOS', 60);
    }

    /** @return array<string, mixed>|null la cuenta logueada (activa), o null */
    public static function cuenta(): ?array
    {
        if (self::$cuenta !== false) {
            return self::$cuenta;
        }
        self::$cuenta = null;
        if (isset($_COOKIE[Auth::COOKIE]) || session_status() === PHP_SESSION_ACTIVE) {
            Auth::iniciarSesion();
            if (isset($_SESSION['cuenta_id'])) {
                self::$cuenta = Db::fila("SELECT * FROM cuentas WHERE id = ? AND estado = 'activa'", [(int) $_SESSION['cuenta_id']]);
                if (self::$cuenta === null) {
                    unset($_SESSION['cuenta_id']); // suspendida o borrada
                }
            }
        }
        return self::$cuenta;
    }

    public static function requerir(): array
    {
        return self::cuenta() ?? Http::redirigir('/cuenta/ingresar');
    }

    /** Genera un link de ingreso y lo manda por email. */
    public static function enviarLink(int $cuentaId, bool $bienvenida = false): bool
    {
        $cuenta = Db::fila('SELECT id, email, nombre FROM cuentas WHERE id = ?', [$cuentaId]);
        if ($cuenta === null) {
            return false;
        }
        $token = bin2hex(random_bytes(24));
        Db::run(
            'INSERT INTO cuenta_tokens (cuenta_id, token_hash, vence_en) VALUES (?, ?, NOW() + INTERVAL ? MINUTE)',
            [$cuentaId, hash('sha256', $token), self::minutosLink()]
        );
        $link = url_abs('/cuenta/entrar/' . $token);
        $saludo = $bienvenida
            ? "¡Hola {$cuenta['nombre']}! Gracias por crear tu cuenta en " . app_nombre() . ".\n\nPara activarla y entrar, hacé clic acá:"
            : "¡Hola {$cuenta['nombre']}!\n\nPara entrar a tu cuenta de " . app_nombre() . ', hacé clic acá:';
        return Mail::enviar(new Email(
            $cuenta['email'],
            $bienvenida ? 'Activá tu cuenta' : 'Tu link para entrar',
            "{$saludo}\n{$link}\n\nEl link sirve una sola vez y vence en " . self::minutosLink() . " minutos.\n"
            . "Si no lo pediste, ignorá este mensaje.\n\n— " . app_nombre()
        ));
    }

    /** @return array<string, mixed>|null token vigente sin usar, con datos de la cuenta */
    public static function tokenVigente(string $token): ?array
    {
        return Db::fila(
            "SELECT t.id AS token_id, c.* FROM cuenta_tokens t JOIN cuentas c ON c.id = t.cuenta_id
             WHERE t.token_hash = ? AND t.usado_en IS NULL AND t.vence_en > NOW() AND c.estado = 'activa'",
            [hash('sha256', $token)]
        );
    }

    /**
     * Usa el token e inicia la sesión. En el primer ingreso confirma el email y le asigna a la
     * cuenta los avisos que esa persona ya había publicado sin cuenta (mismo email).
     */
    public static function entrar(string $token): bool
    {
        $fila = self::tokenVigente($token);
        if ($fila === null) {
            return false;
        }
        Db::run('UPDATE cuenta_tokens SET usado_en = NOW() WHERE id = ? AND usado_en IS NULL', [$fila['token_id']]);
        if ($fila['email_confirmado_en'] === null) {
            Db::run('UPDATE cuentas SET email_confirmado_en = NOW() WHERE id = ?', [$fila['id']]);
            Db::run(
                "UPDATE avisos SET cuenta_id = ?, email_confirmado_en = COALESCE(email_confirmado_en, NOW())
                 WHERE cuenta_id IS NULL AND origen = 'formulario' AND contacto_email = ?",
                [$fila['id'], $fila['email']]
            );
        }
        Db::run('UPDATE cuentas SET ultimo_login = NOW() WHERE id = ?', [$fila['id']]);

        Auth::iniciarSesion();
        session_regenerate_id(true);
        $_SESSION['cuenta_id'] = (int) $fila['id'];
        self::$cuenta = false;
        return true;
    }

    public static function salir(): void
    {
        Auth::iniciarSesion();
        unset($_SESSION['cuenta_id']);
        session_regenerate_id(true);
        self::$cuenta = false;
    }
}
