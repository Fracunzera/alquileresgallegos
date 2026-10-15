<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Catalogo;
use App\Http;
use App\NoEncontrado;
use App\Repositories\AvisoRepo;
use App\Seo\Meta;
use App\Services\Bloqueos;
use App\Services\Denuncias;
use App\Services\FormToken;
use App\Services\RateLimiter;
use App\View;

final class DenunciarController
{
    private const MAX_POR_DIA = 10;

    public function form(string $id): string
    {
        return $this->render($this->aviso($id), [], []);
    }

    public function enviar(string $id): string
    {
        $aviso = $this->aviso($id);
        $v = $_POST;
        // Bots y bloqueados: se finge éxito.
        if ((string) Http::post('sitio_web') !== '' || Bloqueos::coincide(null, Http::post('email'), Http::ip())) {
            return $this->render($aviso, [], [], true);
        }
        if (!FormToken::valido((string) Http::post('_t'), 'denunciar', 3, 3 * 3600)) {
            return $this->render($aviso, $v, ['general' => 'El formulario venció o se envió demasiado rápido. Probá de nuevo.']);
        }
        $clave = 'denuncia:' . Http::ip();
        if (RateLimiter::excedido($clave, self::MAX_POR_DIA)) {
            http_response_code(429);
            return $this->render($aviso, $v, ['general' => 'Recibimos muchas denuncias desde tu conexión. Probá mañana o escribinos.']);
        }

        $err = [];
        $motivo = (string) Http::post('motivo');
        if (!isset(Denuncias::MOTIVOS[$motivo])) {
            $err['motivo'] = 'Elegí un motivo.';
        }
        $detalle = mb_substr(trim((string) Http::post('detalle')), 0, 500) ?: null;
        if ($motivo === 'otro' && $detalle === null) {
            $err['detalle'] = 'Contanos qué pasa con el aviso.';
        }
        $email = mb_strtolower(trim((string) Http::post('email'))) ?: null;
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $err['email'] = 'Revisá el email.';
        } elseif ($motivo === 'mi_numero' && $email === null) {
            $err['email'] = 'Dejanos un email para contactarte: este motivo pausa el aviso al instante.';
        }
        if ($err) {
            return $this->render($aviso, $v, $err);
        }

        RateLimiter::registrar($clave, 86400);
        Denuncias::crear((int) $aviso['id'], $motivo, $detalle, $email, Http::ip());
        return $this->render($aviso, [], [], true);
    }

    /** @return array<string, mixed> */
    private function aviso(string $id): array
    {
        $aviso = (new AvisoRepo())->porId((int) $id);
        if ($aviso === null || $aviso['estado'] !== 'activo') {
            throw new NoEncontrado();
        }
        return $aviso;
    }

    /** @param array<string, mixed> $aviso @param array<string, mixed> $v @param array<string, string> $err */
    private function render(array $aviso, array $v, array $err, bool $enviada = false): string
    {
        header('Cache-Control: private, no-store');
        if ($err && http_response_code() === 200) {
            http_response_code(422);
        }
        return View::render('denunciar', [
            'meta' => new Meta(title: 'Denunciar aviso', description: 'Avisanos si un aviso tiene problemas.', indexar: false),
            'aviso' => $aviso,
            'url' => Catalogo::urlAviso((int) $aviso['id'], $aviso['slug']),
            'v' => $v,
            'err' => $err,
            'enviada' => $enviada,
            'token' => FormToken::generar('denunciar'),
        ]);
    }
}
