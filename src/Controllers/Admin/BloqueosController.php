<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http;
use App\Services\Auth;
use App\Services\Bloqueos;
use App\Services\Flash;

final class BloqueosController extends Base
{
    public function index(): string
    {
        return $this->render('bloqueos', 'Bloqueos', ['items' => Bloqueos::listar(), 'diasIp' => Bloqueos::diasIp()]);
    }

    public function crear(): string
    {
        $tipo = (string) Http::post('tipo');
        if (!isset(Bloqueos::TIPOS[$tipo])) {
            Flash::error('Elegí qué bloquear.');
            Http::redirigir('/admin/bloqueos');
        }
        try {
            Bloqueos::bloquear($tipo, (string) Http::post('valor'), Http::post('motivo') ?: null, $tipo === 'ip' ? Bloqueos::diasIp() : null, Auth::adminId());
            Flash::ok('Bloqueado.');
        } catch (\InvalidArgumentException $e) {
            Flash::error($e->getMessage());
        }
        Http::redirigir('/admin/bloqueos');
    }

    public function borrar(string $id): string
    {
        Bloqueos::desbloquear(self::id($id));
        Flash::ok('Desbloqueado.');
        Http::redirigir('/admin/bloqueos');
    }
}
