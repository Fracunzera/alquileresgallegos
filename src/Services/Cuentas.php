<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use App\Db;
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
    ];

    public static function maxParticular(): int
    {
        return Config::int('CUENTA_MAX_AVISOS_PARTICULAR', 3);
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
        if (empty($entrada['acepto'])) {
            $err['acepto'] = 'Tenés que aceptar las condiciones.';
        }
        return [$d, $err];
    }

    /** @param array<string, mixed> $d salida de validarRegistro() */
    public static function crear(array $d, string $ip): int
    {
        Db::run(
            'INSERT INTO cuentas (email, nombre, whatsapp, tipo, inmobiliaria_solicitada, ip_registro) VALUES (?, ?, ?, ?, ?, ?)',
            [$d['email'], $d['nombre'], $d['whatsapp'], $d['tipo'], $d['inmobiliaria_solicitada'], @inet_pton($ip) ?: null]
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
