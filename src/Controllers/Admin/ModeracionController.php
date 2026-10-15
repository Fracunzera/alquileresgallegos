<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Http;
use App\Repositories\AvisoRepo;
use App\Repositories\BarrioRepo;
use App\Services\AvisoService;
use App\Services\Auth;
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
        $pendientes = Db::filas("SELECT id FROM avisos WHERE estado = 'pendiente' ORDER BY creado_en");
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
            Flash::ok("Aviso n.º {$avisoId} publicado.");
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }
        Http::redirigir('/admin/moderacion');
    }

    public function rechazar(string $id): string
    {
        $avisoId = self::id($id);
        $motivo = trim((string) Http::post('motivo'));
        AvisoService::cambiarEstado($avisoId, 'rechazado', $motivo !== '' ? mb_substr($motivo, 0, 255) : 'Rechazado', Auth::adminId());
        Flash::ok("Aviso n.º {$avisoId} rechazado.");
        Http::redirigir('/admin/moderacion');
    }
}
