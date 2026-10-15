<?php

declare(strict_types=1);

namespace App\Services;

use App\Catalogo;
use App\Config;
use App\Db;
use App\Mail\Mail;

/**
 * Denuncias del público sobre un aviso. Pausa automática:
 *  - "usan mi número": con una sola denuncia (el daño de un acoso es alto; se exige email);
 *  - el resto: cuando hay DENUNCIAS_PARA_PAUSAR denuncias abiertas desde IP distintas.
 * El aviso pausado queda en /admin/denuncias para que se decida.
 */
final class Denuncias
{
    public const MOTIVOS = [
        'alquilado' => 'Ya se alquiló o no está disponible',
        'falso' => 'Es falso o parece una estafa',
        'mi_numero' => 'Usan mi número o mis datos sin permiso',
        'datos' => 'El precio, las fotos o los datos están mal',
        'otro' => 'Otro motivo',
    ];

    /** motivo_estado con el que se pausa; se usa para saber si al descartar hay que reactivar. */
    public const MOTIVO_PAUSA = 'Pausado automáticamente por denuncias';

    public static function umbral(): int
    {
        return Config::int('DENUNCIAS_PARA_PAUSAR', 3);
    }

    /**
     * Registra la denuncia (una abierta por IP y aviso) y pausa el aviso si corresponde.
     *
     * @return bool true si el aviso quedó pausado por esta denuncia
     */
    public static function crear(int $avisoId, string $motivo, ?string $detalle, ?string $email, string $ip): bool
    {
        $ipBin = @inet_pton($ip) ?: null;
        $repetida = Db::valor(
            "SELECT id FROM denuncias WHERE aviso_id = ? AND ip <=> ? AND estado = 'abierta'",
            [$avisoId, $ipBin]
        );
        if ($repetida !== null) {
            return false;
        }
        Db::run(
            'INSERT INTO denuncias (aviso_id, motivo, detalle, email, ip) VALUES (?, ?, ?, ?, ?)',
            [$avisoId, $motivo, $detalle, $email, $ipBin]
        );

        $aviso = Db::fila('SELECT id, titulo, estado FROM avisos WHERE id = ?', [$avisoId]);
        $ipsDistintas = (int) Db::valor(
            "SELECT COUNT(DISTINCT COALESCE(ip, id)) FROM denuncias WHERE aviso_id = ? AND estado = 'abierta'",
            [$avisoId]
        );
        $pausar = $aviso['estado'] === 'activo' && ($motivo === 'mi_numero' || $ipsDistintas >= self::umbral());
        if ($pausar) {
            Db::run("UPDATE avisos SET estado = 'pausado', motivo_estado = ? WHERE id = ?", [self::MOTIVO_PAUSA, $avisoId]);
            AvisoService::registrarVerificacion($avisoId, 'pausado', 'denuncia', self::MOTIVOS[$motivo]);
        }

        Mail::avisarModeracion(
            ($pausar ? 'Aviso PAUSADO por denuncia: ' : 'Denuncia: ') . $aviso['titulo'],
            "Aviso n.º {$avisoId}: {$aviso['titulo']}\nMotivo: " . self::MOTIVOS[$motivo]
            . ($detalle ? "\nDetalle: {$detalle}" : '') . ($email ? "\nEmail de quien denuncia: {$email}" : '')
            . "\nDenuncias abiertas (IP distintas): {$ipsDistintas}"
            . ($pausar ? "\n\nEl aviso se pausó automáticamente hasta que lo revises." : '')
            . "\n\n" . url_abs('/admin/denuncias')
        );
        return $pausar;
    }

    public static function avisosConDenuncias(): int
    {
        return (int) Db::valor("SELECT COUNT(DISTINCT aviso_id) FROM denuncias WHERE estado = 'abierta'");
    }

    /** @return list<array<string, mixed>> avisos con denuncias abiertas, cada uno con sus denuncias */
    public static function abiertas(): array
    {
        $avisos = Db::filas(
            "SELECT a.id, a.titulo, a.slug, a.estado, a.motivo_estado, a.contacto_whatsapp, a.contacto_email, a.origen,
                    COUNT(d.id) AS total, MAX(d.creado_en) AS ultima
             FROM denuncias d JOIN avisos a ON a.id = d.aviso_id
             WHERE d.estado = 'abierta' GROUP BY a.id ORDER BY a.estado = 'pausado' DESC, ultima DESC"
        );
        foreach ($avisos as &$aviso) {
            $aviso['denuncias'] = Db::filas(
                "SELECT * FROM denuncias WHERE aviso_id = ? AND estado = 'abierta' ORDER BY creado_en",
                [$aviso['id']]
            );
        }
        return $avisos;
    }

    /**
     * @param string $accion descartar | alquilado | baja | bloquear
     * @return string mensaje para el panel
     */
    public static function resolver(int $avisoId, string $accion, ?int $adminId): string
    {
        $aviso = Db::fila('SELECT * FROM avisos WHERE id = ?', [$avisoId]) ?? throw new \RuntimeException('El aviso no existe.');
        $estadoDenuncias = $accion === 'descartar' ? 'descartada' : 'resuelta';
        Db::run(
            "UPDATE denuncias SET estado = ?, resuelta_en = NOW(), admin_id = ? WHERE aviso_id = ? AND estado = 'abierta'",
            [$estadoDenuncias, $adminId, $avisoId]
        );

        switch ($accion) {
            case 'descartar':
                if ($aviso['estado'] === 'pausado' && $aviso['motivo_estado'] === self::MOTIVO_PAUSA) {
                    AvisoService::cambiarEstado($avisoId, 'activo', null, $adminId);
                    return "Denuncias descartadas. El aviso n.º {$avisoId} volvió a publicarse.";
                }
                return 'Denuncias descartadas.';
            case 'alquilado':
                AvisoService::cambiarEstado($avisoId, 'alquilado', 'Denunciado como ya alquilado', $adminId);
                return "Aviso n.º {$avisoId} marcado alquilado.";
            case 'baja':
                AvisoService::cambiarEstado($avisoId, 'rechazado', 'Dado de baja por denuncias', $adminId);
                return "Aviso n.º {$avisoId} dado de baja.";
            case 'bloquear':
                AvisoService::cambiarEstado($avisoId, 'rechazado', 'Dado de baja por denuncias', $adminId);
                $bloqueado = Bloqueos::bloquearAnunciante($aviso, "Denuncias sobre el aviso n.º {$avisoId}", $adminId);
                return "Aviso n.º {$avisoId} dado de baja. Bloqueado: " . ($bloqueado ? implode(', ', $bloqueado) : 'sin datos') . '.';
        }
        throw new \InvalidArgumentException("Acción inválida: {$accion}");
    }

    /** @param array<string, mixed> $aviso */
    public static function urlDenunciar(array $aviso): string
    {
        return '/aviso/' . (int) $aviso['id'] . '/denunciar';
    }
}
