<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Catalogo;
use App\Frescura\ColaVerificacion;
use App\Http;
use App\Seo\Meta;
use App\Services\AvisoService;
use App\View;

/**
 * Link que recibe el anunciante para confirmar si su aviso sigue disponible.
 * El GET solo muestra botones: WhatsApp y los clientes de email abren los links para armar la vista
 * previa, así que la confirmación tiene que ser un POST.
 */
final class VerificarController
{
    public function ver(string $id, string $token): string
    {
        $elegida = in_array(Http::query('r'), ['disponible', 'alquilado'], true) ? (string) Http::query('r') : null;
        return $this->pagina(ColaVerificacion::porToken((int) $id, $token), $token, null, $elegida);
    }

    public function responder(string $id, string $token): string
    {
        $aviso = ColaVerificacion::porToken((int) $id, $token);
        if ($aviso === null) {
            return $this->pagina(null, $token, null);
        }
        $avisoId = (int) $aviso['id'];
        $respuesta = Http::post('respuesta');
        $pausado = ColaVerificacion::pausadoSinRespuesta($aviso);
        if ($aviso['estado'] !== 'activo' && !$pausado) {
            return $this->pagina($aviso, $token, null);
        }
        try {
            if ($respuesta === 'disponible' && $pausado) {
                AvisoService::cambiarEstado($avisoId, 'activo'); // cuenta como verificado y cierra el pedido
                $mensaje = '¡Listo! Tu aviso está publicado de nuevo. Te vamos a volver a preguntar en ' . ColaVerificacion::diasVerificacion() . ' días.';
            } elseif ($respuesta === 'disponible') {
                ColaVerificacion::confirmar($avisoId, 'link');
                $mensaje = '¡Gracias! Tu aviso sigue publicado. Te vamos a volver a preguntar en ' . ColaVerificacion::diasVerificacion() . ' días.';
            } elseif ($respuesta === 'alquilado') {
                AvisoService::cambiarEstado($avisoId, 'alquilado', 'El anunciante avisó que se alquiló');
                ColaVerificacion::anularSolicitud($avisoId);
                $mensaje = '¡Felicitaciones! Marcamos el aviso como alquilado y ya no recibe consultas.';
            } else {
                return $this->pagina($aviso, $token, null);
            }
        } catch (\RuntimeException $e) {
            return $this->pagina($aviso, $token, null, null, $e->getMessage());
        }
        return $this->pagina(null, $token, $mensaje);
    }

    /** @param array<string, mixed>|null $aviso */
    private function pagina(?array $aviso, string $token, ?string $mensaje, ?string $elegida = null, ?string $error = null): string
    {
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow');
        if ($aviso === null && $mensaje === null) {
            http_response_code(404);
        }
        return View::render('verificar', [
            'meta' => new Meta(title: 'Confirmá tu aviso', description: 'Confirmá si tu aviso sigue disponible.', indexar: false),
            'aviso' => $aviso,
            'token' => $token,
            'mensaje' => $mensaje,
            'elegida' => $elegida,
            'error' => $error,
            'pausado' => $aviso !== null && ColaVerificacion::pausadoSinRespuesta($aviso),
            'precio' => $aviso ? Catalogo::precio($aviso) : null,
        ]);
    }
}
