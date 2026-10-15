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
        return $this->pagina(ColaVerificacion::porToken((int) $id, $token), $token, null);
    }

    public function responder(string $id, string $token): string
    {
        $aviso = ColaVerificacion::porToken((int) $id, $token);
        if ($aviso === null) {
            return $this->pagina(null, $token, null);
        }
        $respuesta = Http::post('respuesta');
        if ($respuesta === 'disponible' && $aviso['estado'] === 'activo') {
            ColaVerificacion::confirmar((int) $aviso['id'], 'link');
            $mensaje = '¡Gracias! Tu aviso sigue publicado. Te vamos a volver a preguntar en ' . ColaVerificacion::diasVerificacion() . ' días.';
        } elseif ($respuesta === 'alquilado') {
            AvisoService::cambiarEstado((int) $aviso['id'], 'alquilado', 'El anunciante avisó que se alquiló');
            \App\Db::run('UPDATE avisos SET token_gestion = NULL, verificacion_solicitada_en = NULL WHERE id = ?', [$aviso['id']]);
            $mensaje = '¡Felicitaciones! Marcamos el aviso como alquilado y ya no recibe consultas.';
        } else {
            return $this->pagina($aviso, $token, null);
        }
        return $this->pagina(null, $token, $mensaje);
    }

    /** @param array<string, mixed>|null $aviso */
    private function pagina(?array $aviso, string $token, ?string $mensaje): string
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
            'precio' => $aviso ? Catalogo::precio($aviso) : null,
        ]);
    }
}
