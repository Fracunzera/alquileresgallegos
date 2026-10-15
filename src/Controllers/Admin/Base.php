<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http;
use App\Services\Auth;
use App\Services\Csrf;
use App\View;

/**
 * Todo controlador del panel exige sesión y verifica CSRF en cada POST.
 */
abstract class Base
{
    public function __construct()
    {
        header('X-Robots-Tag: noindex, nofollow');
        Auth::requerir();
        if (Http::metodo() === 'POST') {
            Csrf::verificar();
        }
    }

    /** @param array<string, mixed> $datos */
    protected function render(string $plantilla, string $titulo, array $datos = []): string
    {
        return View::render('admin/' . $plantilla, ['titulo' => $titulo] + $datos, 'admin/layout');
    }

    protected static function id(string $id): int
    {
        return (int) $id;
    }
}
