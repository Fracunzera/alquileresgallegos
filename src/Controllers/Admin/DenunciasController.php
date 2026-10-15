<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http;
use App\Services\Auth;
use App\Services\Denuncias;
use App\Services\Flash;

final class DenunciasController extends Base
{
    public function index(): string
    {
        return $this->render('denuncias', 'Denuncias', ['items' => Denuncias::abiertas(), 'umbral' => Denuncias::umbral()]);
    }

    public function resolver(string $id, string $accion): string
    {
        try {
            Flash::ok(Denuncias::resolver(self::id($id), $accion, Auth::adminId()));
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }
        Http::redirigir('/admin/denuncias');
    }
}
