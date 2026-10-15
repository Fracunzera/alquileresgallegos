<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Http;
use App\Repositories\AvisoRepo;
use App\Repositories\BarrioRepo;
use App\Services\AvisoService;
use App\Services\Auth;
use App\Services\Bloqueos;
use App\Services\Confirmacion;
use App\Services\Flash;

/**
 * Avisos que llegaron por /publicar. Se aprueban (publican) o se rechazan con motivo.
 */
final class ModeracionController extends Base
{
    public const MOTIVOS = [
        'Datos incompletos o confusos',
        'Aviso duplicado',
        'La propiedad no está en Río Gallegos',
        'No es un alquiler',
        'Spam o contenido inapropiado',
    ];

    public function index(): string
    {
        $repo = new AvisoRepo();
        $pendientes = Db::filas("SELECT id FROM avisos WHERE estado = 'pendiente' AND email_confirmado_en IS NOT NULL ORDER BY creado_en");
        $items = array_map(fn ($fila) => $repo->porId((int) $fila['id']) + ['fotos' => $repo->fotos((int) $fila['id'])], $pendientes);
        foreach ($items as &$item) {
            // Mismo WhatsApp en otros avisos: ayuda a detectar duplicados e inmobiliarias encubiertas.
            $item['otros_mismo_contacto'] = $item['contacto_whatsapp'] ? (int) Db::valor(
                'SELECT COUNT(*) FROM avisos WHERE contacto_whatsapp = ? AND id <> ?',
                [$item['contacto_whatsapp'], $item['id']]
            ) : 0;
        }
        unset($item);

        return $this->render('moderacion', 'Moderación', [
            'items' => $items,
            'barrios' => (new BarrioRepo())->todos(),
            'motivos' => self::MOTIVOS,
            'sinConfirmar' => Db::filas(
                "SELECT id, titulo, contacto_email, creado_en, slug FROM avisos
                 WHERE estado = 'pendiente' AND email_confirmado_en IS NULL ORDER BY creado_en DESC"
            ),
            'horas' => Confirmacion::horas(),
        ]);
    }

    public function aprobar(string $id): string
    {
        $avisoId = self::id($id);
        $barrio = Http::post('barrio_id');
        if ($barrio !== null && ctype_digit($barrio)) {
            Db::run('UPDATE avisos SET barrio_id = ?, barrio_sugerido = NULL WHERE id = ?', [(int) $barrio, $avisoId]);
        }
        try {
            AvisoService::cambiarEstado($avisoId, 'activo', null, Auth::adminId());
            Confirmacion::avisarAprobado(Db::fila('SELECT * FROM avisos WHERE id = ?', [$avisoId]));
            Flash::ok("Aviso n.º {$avisoId} publicado.");
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }
        Http::redirigir('/admin/moderacion');
    }

    /** Rechaza sin avisarle al anunciante y bloquea su WhatsApp, email e IP. */
    public function bloquear(string $id): string
    {
        $aviso = Db::fila('SELECT * FROM avisos WHERE id = ?', [self::id($id)]);
        if ($aviso !== null) {
            $motivo = 'Spam o contenido inapropiado';
            AvisoService::cambiarEstado((int) $aviso['id'], 'rechazado', $motivo, Auth::adminId());
            $bloqueado = Bloqueos::bloquearAnunciante($aviso, $motivo . " (aviso n.º {$aviso['id']})", Auth::adminId());
            Flash::ok("Aviso n.º {$aviso['id']} rechazado. Bloqueado: " . ($bloqueado ? implode(', ', $bloqueado) : 'sin datos para bloquear') . '.');
        }
        Http::redirigir('/admin/moderacion');
    }

    /** Confirmar a mano un aviso cuyo email no se confirmó (ej. no le llegó el correo). */
    public function confirmarEmail(string $id): string
    {
        Db::run("UPDATE avisos SET email_confirmado_en = NOW(), token_confirmacion = NULL WHERE id = ? AND estado = 'pendiente'", [self::id($id)]);
        Flash::ok("Aviso n.º {$id} pasado a moderación.");
        Http::redirigir('/admin/moderacion');
    }

    public function rechazar(string $id): string
    {
        $avisoId = self::id($id);
        $motivo = trim((string) Http::post('motivo'));
        $motivo = $motivo !== '' ? mb_substr($motivo, 0, 255) : 'Rechazado';
        AvisoService::cambiarEstado($avisoId, 'rechazado', $motivo, Auth::adminId());
        Confirmacion::avisarRechazado(Db::fila('SELECT * FROM avisos WHERE id = ?', [$avisoId]), $motivo);
        Flash::ok("Aviso n.º {$avisoId} rechazado.");
        Http::redirigir('/admin/moderacion');
    }
}
