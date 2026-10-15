<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use App\Db;

/**
 * Lista de bloqueo por WhatsApp, email o IP. Lo que llega de un bloqueado se descarta
 * en silencio (se le muestra "éxito" para no darle pistas).
 */
final class Bloqueos
{
    public const TIPOS = ['whatsapp' => 'WhatsApp', 'email' => 'Email', 'ip' => 'IP'];

    public static function diasIp(): int
    {
        return Config::int('BLOQUEO_IP_DIAS', 7);
    }

    /** Normaliza el valor según el tipo; null si no es válido. */
    public static function normalizar(string $tipo, ?string $valor): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        return match ($tipo) {
            'whatsapp' => Telefono::whatsapp($valor),
            'email' => filter_var(mb_strtolower($valor), FILTER_VALIDATE_EMAIL) ?: null,
            'ip' => ($bin = @inet_pton($valor)) !== false ? inet_ntop($bin) : null,
            default => null,
        };
    }

    /**
     * @throws \InvalidArgumentException si el valor no es válido para el tipo
     */
    public static function bloquear(string $tipo, string $valor, ?string $motivo, ?int $dias = null, ?int $adminId = null, ?int $avisoId = null): void
    {
        $normalizado = self::normalizar($tipo, $valor) ?? throw new \InvalidArgumentException('El valor no es un ' . (self::TIPOS[$tipo] ?? $tipo) . ' válido.');
        $vence = $dias !== null ? date('Y-m-d H:i:s', strtotime("+{$dias} days")) : null;
        $motivo = $motivo !== null ? mb_substr($motivo, 0, 255) : null;
        Db::run(
            'INSERT INTO bloqueos (tipo, valor, motivo, vence_en, aviso_id, admin_id) VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE motivo = ?, vence_en = ?, aviso_id = ?, admin_id = ?',
            [$tipo, $normalizado, $motivo, $vence, $avisoId, $adminId, $motivo, $vence, $avisoId, $adminId]
        );
    }

    /**
     * Bloquea al anunciante de un aviso: WhatsApp y email para siempre, IP por unos días.
     *
     * @param array<string, mixed> $aviso
     * @return list<string> descripción de lo bloqueado
     */
    public static function bloquearAnunciante(array $aviso, string $motivo, ?int $adminId): array
    {
        $hecho = [];
        if ($aviso['contacto_whatsapp']) {
            self::bloquear('whatsapp', $aviso['contacto_whatsapp'], $motivo, null, $adminId, (int) $aviso['id']);
            $hecho[] = 'WhatsApp ' . $aviso['contacto_whatsapp'];
        }
        if ($aviso['contacto_email']) {
            self::bloquear('email', $aviso['contacto_email'], $motivo, null, $adminId, (int) $aviso['id']);
            $hecho[] = 'email ' . $aviso['contacto_email'];
        }
        if ($aviso['ip_origen']) {
            $ip = inet_ntop($aviso['ip_origen']);
            if ($ip !== false) {
                self::bloquear('ip', $ip, $motivo, self::diasIp(), $adminId, (int) $aviso['id']);
                $hecho[] = "IP {$ip} (" . self::diasIp() . ' días)';
            }
        }
        return $hecho;
    }

    /** ¿Alguno de estos datos está bloqueado (y vigente)? */
    public static function coincide(?string $whatsapp = null, ?string $email = null, ?string $ip = null): bool
    {
        $condiciones = [];
        $params = [];
        foreach (['whatsapp' => $whatsapp, 'email' => $email, 'ip' => $ip] as $tipo => $valor) {
            $normalizado = self::normalizar($tipo, $valor);
            if ($normalizado !== null) {
                $condiciones[] = '(tipo = ? AND valor = ?)';
                array_push($params, $tipo, $normalizado);
            }
        }
        if (!$condiciones) {
            return false;
        }
        return Db::valor(
            'SELECT 1 FROM bloqueos WHERE (' . implode(' OR ', $condiciones) . ') AND (vence_en IS NULL OR vence_en > NOW()) LIMIT 1',
            $params
        ) !== null;
    }

    /** @return list<array<string, mixed>> */
    public static function listar(): array
    {
        return Db::filas(
            'SELECT b.*, a.nombre AS admin_nombre, (b.vence_en IS NOT NULL AND b.vence_en <= NOW()) AS vencido
             FROM bloqueos b LEFT JOIN admins a ON a.id = b.admin_id ORDER BY b.creado_en DESC'
        );
    }

    public static function desbloquear(int $id): void
    {
        Db::run('DELETE FROM bloqueos WHERE id = ?', [$id]);
    }
}
