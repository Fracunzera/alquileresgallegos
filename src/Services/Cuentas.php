<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use App\Db;
use App\Frescura\ColaVerificacion;
use App\Mail\Mail;

/**
 * Reglas de las cuentas de anunciantes:
 *  - particulares: hasta CUENTA_MAX_AVISOS_PARTICULAR avisos vigentes (pendientes, activos o pausados);
 *  - inmobiliarias: sin límite, pero recién pueden publicar cuando se vinculan a su ficha desde el panel;
 *  - cuentas no confiables: sus avisos nuevos pasan por moderación y sus ediciones quedan marcadas para revisar.
 */
final class Cuentas
{
    /** Estados con los que el propio anunciante pausó o dio de baja el aviso (puede reactivarlo). */
    public const MOTIVOS_DEL_ANUNCIANTE = [
        'Pausado por el anunciante',
        'Marcado alquilado por el anunciante',
        'El anunciante avisó que se alquiló',
        ColaVerificacion::MOTIVO_SIN_RESPUESTA, // no contestó el "¿sigue disponible?": lo puede volver a publicar
    ];

    public static function maxParticular(): int
    {
        return Config::int('CUENTA_MAX_AVISOS_PARTICULAR', 3);
    }

    public const CLAVE_MINIMO = 8;

    /** Error para mostrar, o null si la contraseña sirve. */
    public static function validarClave(string $clave, string $repetida): ?string
    {
        if (mb_strlen($clave) < self::CLAVE_MINIMO) {
            return 'La contraseña tiene que tener al menos ' . self::CLAVE_MINIMO . ' caracteres.';
        }
        if (strlen($clave) > 72) {
            return 'La contraseña es demasiado larga (máximo 72 caracteres).';
        }
        if ($clave !== $repetida) {
            return 'Las dos contraseñas no coinciden.';
        }
        return null;
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public static function validarRegistro(array $entrada): array
    {
        $txt = static fn (string $k): string => trim((string) ($entrada[$k] ?? ''));
        $err = [];
        $d = [
            'nombre' => mb_substr($txt('nombre'), 0, 120),
            'email' => mb_strtolower($txt('email')),
            'tipo' => $txt('tipo') === 'inmobiliaria' ? 'inmobiliaria' : 'particular',
            'inmobiliaria_solicitada' => null,
        ];
        if (mb_strlen($d['nombre']) < 2) {
            $err['nombre'] = 'Escribí tu nombre.';
        }
        if (filter_var($d['email'], FILTER_VALIDATE_EMAIL) === false) {
            $err['email'] = 'Revisá el email: ahí te llega el link para entrar.';
        }
        $wa = $txt('whatsapp');
        $d['whatsapp'] = $wa !== '' ? Telefono::whatsapp($wa) : null;
        if ($wa !== '' && $d['whatsapp'] === null) {
            $err['whatsapp'] = 'Revisá el número de WhatsApp (ej: 2966 15 123456).';
        }
        if ($d['tipo'] === 'inmobiliaria') {
            $d['inmobiliaria_solicitada'] = mb_substr($txt('inmobiliaria'), 0, 120) ?: null;
            if ($d['inmobiliaria_solicitada'] === null) {
                $err['inmobiliaria'] = 'Escribí el nombre de la inmobiliaria.';
            }
        }
        $errorClave = self::validarClave((string) ($entrada['clave'] ?? ''), (string) ($entrada['clave_repetida'] ?? ''));
        if ($errorClave !== null) {
            $err['clave'] = $errorClave;
        }
        $d['clave'] = (string) ($entrada['clave'] ?? '');
        if (empty($entrada['acepto'])) {
            $err['acepto'] = 'Tenés que aceptar las condiciones.';
        }
        return [$d, $err];
    }

    /** @param array<string, mixed> $d salida de validarRegistro() */
    public static function crear(array $d, string $ip): int
    {
        Db::run(
            'INSERT INTO cuentas (email, password_hash, nombre, whatsapp, tipo, inmobiliaria_solicitada, ip_registro) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$d['email'], password_hash($d['clave'], PASSWORD_DEFAULT), $d['nombre'], $d['whatsapp'], $d['tipo'], $d['inmobiliaria_solicitada'], @inet_pton($ip) ?: null]
        );
        $id = (int) Db::pdo()->lastInsertId();
        if ($d['tipo'] === 'inmobiliaria') {
            Mail::avisarModeracion(
                'Inmobiliaria para vincular: ' . $d['inmobiliaria_solicitada'],
                "{$d['nombre']} ({$d['email']}) creó una cuenta de la inmobiliaria «{$d['inmobiliaria_solicitada']}».\n"
                . "Vinculala a su ficha para que pueda publicar:\n" . url_abs('/admin/cuentas')
            );
        }
        return $id;
    }

    /** Avisos que cuentan para el límite de un particular. */
    public static function avisosVigentes(int $cuentaId): int
    {
        return (int) Db::valor(
            "SELECT COUNT(*) FROM avisos WHERE cuenta_id = ? AND estado IN ('pendiente','activo','pausado')",
            [$cuentaId]
        );
    }

    /**
     * ¿Puede sumar un aviso vigente más (nuevo o reactivado)?
     *
     * @param array<string, mixed> $cuenta
     * @return array{0: bool, 1: ?string} [puede, motivo si no puede]
     */
    public static function puedePublicar(array $cuenta): array
    {
        if ($cuenta['tipo'] === 'inmobiliaria') {
            return $cuenta['inmobiliaria_id']
                ? [true, null]
                : [false, 'Estamos verificando tu inmobiliaria. Te avisamos por email apenas puedas publicar (normalmente en menos de 24 horas).'];
        }
        $max = self::maxParticular();
        return self::avisosVigentes((int) $cuenta['id']) < $max
            ? [true, null]
            : [false, "Las cuentas de particulares pueden tener hasta {$max} avisos a la vez. Marcá como alquilado alguno que ya no esté disponible, o escribinos si sos inmobiliaria."];
    }

    /**
     * Vincula una cuenta de inmobiliaria a su ficha: desde ahí publica sin límite y sus avisos
     * salen con el nombre de la inmobiliaria.
     */
    public static function vincularInmobiliaria(int $cuentaId, int $inmobiliariaId): void
    {
        Db::run("UPDATE cuentas SET tipo = 'inmobiliaria', inmobiliaria_id = ? WHERE id = ?", [$inmobiliariaId, $cuentaId]);
        Db::run("UPDATE avisos SET publicador = 'inmobiliaria', inmobiliaria_id = ? WHERE cuenta_id = ?", [$inmobiliariaId, $cuentaId]);
        $cuenta = Db::fila('SELECT c.email, c.nombre, i.nombre AS inmobiliaria FROM cuentas c JOIN inmobiliarias i ON i.id = c.inmobiliaria_id WHERE c.id = ?', [$cuentaId]);
        Mail::enviar(new \App\Mail\Email(
            $cuenta['email'],
            '¡Tu cuenta de inmobiliaria está lista!',
            "¡Hola {$cuenta['nombre']}!\n\nVerificamos tu cuenta de {$cuenta['inmobiliaria']}. Ya podés publicar todos tus avisos desde tu panel:\n"
            . url_abs('/cuenta') . "\n\n— " . app_nombre()
        ));
    }

    /** Suspende la cuenta (no puede entrar) y pausa sus avisos publicados. */
    public static function suspender(int $cuentaId, ?int $adminId): int
    {
        Db::run("UPDATE cuentas SET estado = 'suspendida' WHERE id = ?", [$cuentaId]);
        $ids = array_column(Db::filas("SELECT id FROM avisos WHERE cuenta_id = ? AND estado IN ('activo','pendiente')", [$cuentaId]), 'id');
        foreach ($ids as $id) {
            AvisoService::cambiarEstado((int) $id, 'pausado', 'Cuenta suspendida', $adminId);
        }
        return count($ids);
    }

    /**
     * Completa los datos de un aviso con los de la cuenta (quién publica).
     *
     * @param array<string, mixed> $datos salida de AvisoService::validar()
     * @param array<string, mixed> $cuenta
     * @return array<string, mixed>
     */
    public static function aplicarPublicador(array $datos, array $cuenta): array
    {
        $esInmobiliaria = $cuenta['tipo'] === 'inmobiliaria' && $cuenta['inmobiliaria_id'];
        $datos['publicador'] = $esInmobiliaria ? 'inmobiliaria' : 'particular';
        $datos['inmobiliaria_id'] = $esInmobiliaria ? (int) $cuenta['inmobiliaria_id'] : null;
        return $datos;
    }
}
