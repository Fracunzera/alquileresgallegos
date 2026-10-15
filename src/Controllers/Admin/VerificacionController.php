<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Frescura\ColaVerificacion;
use App\Http;
use App\Services\AvisoService;
use App\Services\Auth;
use App\Services\Flash;

/**
 * Cola de avisos sin verificar hace más de FRESCURA_DIAS_VERIFICACION días.
 */
final class VerificacionController extends Base
{
    public function index(): string
    {
        $ultima = $_SESSION['ultima_solicitud'] ?? null;
        unset($_SESSION['ultima_solicitud']);
        return $this->render('verificacion', 'Verificación', [
            'items' => ColaVerificacion::pendientes(),
            'dias' => ColaVerificacion::diasVerificacion(),
            'gracia' => ColaVerificacion::diasGracia(),
            'ultima' => $ultima,
        ]);
    }

    public function accion(string $id, string $accion): string
    {
        $avisoId = self::id($id);
        try {
            match ($accion) {
                'confirmar' => ColaVerificacion::confirmar($avisoId, 'manual', Auth::adminId()),
                'alquilado' => AvisoService::cambiarEstado($avisoId, 'alquilado', 'Marcado desde la cola de verificación', Auth::adminId()),
                'pausar' => AvisoService::cambiarEstado($avisoId, 'pausado', 'Pausado desde la cola de verificación', Auth::adminId()),
                'solicitar' => $_SESSION['ultima_solicitud'] = ['aviso_id' => $avisoId] + ColaVerificacion::solicitar($avisoId, Auth::adminId()),
            };
            Flash::ok(match ($accion) {
                'confirmar' => "Aviso n.º {$avisoId} confirmado como disponible.",
                'alquilado' => "Aviso n.º {$avisoId} marcado alquilado.",
                'pausar' => "Aviso n.º {$avisoId} pausado.",
                'solicitar' => "Pedido de confirmación generado para el aviso n.º {$avisoId}.",
            });
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }
        Http::redirigir('/admin/verificacion');
    }
}
