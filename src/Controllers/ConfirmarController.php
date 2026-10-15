<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http;
use App\Seo\Meta;
use App\Services\Confirmacion;
use App\View;

/**
 * Link del email de confirmación. Como en /verificar, el GET solo muestra un botón:
 * algunos filtros de correo abren los links para analizarlos y no deben confirmar nada.
 */
final class ConfirmarController
{
    public function ver(string $id, string $token): string
    {
        return $this->pagina(Confirmacion::porToken((int) $id, $token), false);
    }

    public function confirmar(string $id, string $token): string
    {
        $aviso = Confirmacion::porToken((int) $id, $token);
        if ($aviso !== null && Http::post('confirmar') === '1') {
            Confirmacion::confirmar((int) $aviso['id']);
            return $this->pagina($aviso, true);
        }
        return $this->pagina($aviso, false);
    }

    /** @param array<string, mixed>|null $aviso */
    private function pagina(?array $aviso, bool $confirmado): string
    {
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow');
        if ($aviso === null) {
            http_response_code(404);
        }
        return View::render('confirmar', [
            'meta' => new Meta(title: 'Confirmá tu aviso', description: 'Confirmá tu email para publicar tu aviso.', indexar: false),
            'aviso' => $aviso,
            'confirmado' => $confirmado,
        ]);
    }
}
