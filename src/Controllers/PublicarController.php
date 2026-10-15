<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Http;
use App\Repositories\BarrioRepo;
use App\Seo\Meta;
use App\Services\AvisoService;
use App\Services\Confirmacion;
use App\Services\FormToken;
use App\Services\Fotos;
use App\Services\RateLimiter;
use App\View;

/**
 * Formulario público, sin registro. Lo que llega queda "pendiente" hasta que se modera.
 * Anti-spam sin captcha: campo trampa, token firmado con tiempo mínimo de llenado y límite por IP.
 */
final class PublicarController
{
    private const SEGUNDOS_MINIMOS = 5;
    private const SEGUNDOS_MAXIMOS = 3 * 3600;

    public function form(): string
    {
        return $this->render(['tipo' => 'mensual', 'periodo' => 'mes', 'moneda' => 'ARS'], []);
    }

    public function enviar(): string
    {
        // Bots: el campo trampa está oculto para personas; si viene lleno se finge éxito.
        if (Http::post('sitio_web') !== '' && Http::post('sitio_web') !== null) {
            Http::redirigir('/publicar/gracias', 303);
        }
        if (!FormToken::valido((string) Http::post('_t'), 'publicar', self::SEGUNDOS_MINIMOS, self::SEGUNDOS_MAXIMOS)) {
            return $this->render($_POST, ['general' => 'El formulario venció o se envió demasiado rápido. Revisá los datos y volvé a enviarlo.']);
        }
        $clave = 'publicar:' . Http::ip();
        $maximo = Config::int('PUBLICAR_MAX_POR_DIA', 5);
        if (RateLimiter::excedido($clave, $maximo)) {
            http_response_code(429);
            return $this->render($_POST, ['general' => "Llegaste al máximo de {$maximo} avisos por día. Si sos inmobiliaria, escribinos para cargarlos juntos."]);
        }

        [$datos, $errores] = AvisoService::validar($_POST, true);
        if (empty($_POST['acepto'])) {
            $errores['acepto'] = 'Tenés que aceptar las condiciones para publicar.';
        }
        if ($errores) {
            return $this->render($_POST, $errores);
        }

        RateLimiter::registrar($clave, 86400);
        $id = AvisoService::crear($datos, 'formulario', 'pendiente', Http::ip());
        Fotos::agregarSubidas($id, $_FILES['fotos'] ?? [], Config::int('FOTOS_MAX_POR_AVISO', 12));
        Confirmacion::enviar($id);
        Http::redirigir('/publicar/gracias', 303);
    }

    public function gracias(): string
    {
        header('Cache-Control: private, no-store');
        return View::render('publicar-gracias', [
            'meta' => new Meta(title: '¡Recibimos tu aviso!', description: 'Tu aviso está en revisión.', indexar: false),
        ]);
    }

    /** @param array<string, mixed> $valores @param array<string, string> $errores */
    private function render(array $valores, array $errores): string
    {
        header('Cache-Control: private, no-store');
        if ($errores && http_response_code() === 200) {
            http_response_code(422);
        }
        return View::render('publicar', [
            'meta' => new Meta(
                title: 'Publicar alquiler gratis en Río Gallegos',
                description: 'Publicá gratis tu casa, departamento o monoambiente en alquiler en Río Gallegos. Sin registrarte, con fotos y contacto directo por WhatsApp.',
                canonical: '/publicar',
            ),
            'v' => $valores,
            'err' => $errores,
            'barrios' => (new BarrioRepo())->activos(),
            'token' => FormToken::generar('publicar'),
        ]);
    }
}
