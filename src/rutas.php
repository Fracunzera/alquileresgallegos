<?php

declare(strict_types=1);

use App\Controllers\AvisoController;
use App\Controllers\HomeController;
use App\Controllers\InmobiliariaController;
use App\Controllers\LandingController;
use App\Router;
use App\Seo\Landings;

return static function (Router $r): void {
    // Públicas (todas renderizadas en el servidor)
    $r->get('/', [HomeController::class, 'index']);
    foreach (array_keys(Landings::fijas()) as $path) {
        $r->get($path, fn () => (new LandingController())->fija($path));
    }
    $r->get('/buscar', [LandingController::class, 'buscar']);
    $r->get('/alquileres-{slug:[a-z0-9-]+}-rio-gallegos', [LandingController::class, 'barrio']);
    $r->get('/aviso/{id:\d+}-{slug:[a-z0-9-]*}', [AvisoController::class, 'ver']);
    $r->get('/aviso/{id:\d+}', fn (string $id) => (new AvisoController())->ver($id, ''));
    $r->get('/inmobiliaria/{slug:[a-z0-9-]+}', [InmobiliariaController::class, 'ver']);
};
