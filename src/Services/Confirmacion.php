<?php

declare(strict_types=1);

namespace App\Services;

use App\Catalogo;
use App\Config;
use App\Db;
use App\Mail\Email;
use App\Mail\Mail;

/**
 * Emails al anunciante (aviso aprobado / rechazado) y restos del formulario anónimo que existió
 * antes de exigir cuenta: confirmación por link y limpieza de los que nunca se confirmaron.
 */
final class Confirmacion
{
    public static function horas(): int
    {
        return Config::int('CONFIRMACION_HORAS', 72);
    }

    /** @return array<string, mixed>|null el aviso si el token corresponde a un aviso sin confirmar */
    public static function porToken(int $avisoId, string $token): ?array
    {
        $aviso = Db::fila('SELECT * FROM avisos WHERE id = ?', [$avisoId]);
        if ($aviso === null || $aviso['token_confirmacion'] === null || !hash_equals($aviso['token_confirmacion'], hash('sha256', $token))) {
            return null;
        }
        return $aviso;
    }

    /** Marca el email como confirmado y avisa a moderación. */
    public static function confirmar(int $avisoId): void
    {
        Db::run('UPDATE avisos SET email_confirmado_en = NOW(), token_confirmacion = NULL WHERE id = ?', [$avisoId]);
        $aviso = Db::fila('SELECT id, titulo FROM avisos WHERE id = ?', [$avisoId]);
        Mail::avisarModeracion(
            "Aviso para moderar: {$aviso['titulo']}",
            "Hay un aviso nuevo con email confirmado esperando moderación:\n{$aviso['titulo']}\n\n" . url_abs('/admin/moderacion')
        );
    }

    /**
     * Borra los avisos de /publicar que no confirmaron el email a tiempo (con sus fotos).
     *
     * @return int cantidad borrada
     */
    public static function limpiarVencidos(): int
    {
        $ids = array_column(Db::filas(
            "SELECT id FROM avisos WHERE origen = 'formulario' AND estado = 'pendiente' AND email_confirmado_en IS NULL
             AND creado_en < NOW() - INTERVAL ? HOUR",
            [self::horas()]
        ), 'id');
        foreach ($ids as $id) {
            AvisoService::borrar((int) $id);
        }
        return count($ids);
    }

    /** Email al anunciante cuando se aprueba su aviso. @param array<string, mixed> $aviso */
    public static function avisarAprobado(array $aviso): void
    {
        if ($aviso['origen'] === 'admin' || !$aviso['contacto_email']) {
            return;
        }
        Mail::enviar(new Email(
            $aviso['contacto_email'],
            '¡Tu aviso ya está publicado!',
            "¡Hola!\n\nTu aviso \"{$aviso['titulo']}\" ya está publicado en " . app_nombre() . ":\n"
            . url_abs(Catalogo::urlAviso((int) $aviso['id'], $aviso['slug'])) . "\n\n"
            . "Compartilo por WhatsApp para que llegue a más gente. Cada tanto te vamos a escribir para preguntarte si sigue disponible.\n\n— " . app_nombre()
        ));
    }

    /** Email al anunciante cuando se rechaza su aviso. @param array<string, mixed> $aviso */
    public static function avisarRechazado(array $aviso, string $motivo): void
    {
        if ($aviso['origen'] === 'admin' || !$aviso['contacto_email']) {
            return;
        }
        Mail::enviar(new Email(
            $aviso['contacto_email'],
            'No pudimos publicar tu aviso',
            "¡Hola!\n\nRevisamos tu aviso \"{$aviso['titulo']}\" y no lo pudimos publicar.\nMotivo: {$motivo}.\n\n"
            . 'Podés corregirlo y volver a enviarlo desde tu cuenta: ' . url_abs('/cuenta') . "\n\n— " . app_nombre()
        ));
    }
}
