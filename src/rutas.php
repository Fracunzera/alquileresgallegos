<?php

declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\AvisoController;
use App\Controllers\ConfirmarController;
use App\Controllers\DenunciarController;
use App\Controllers\HomeController;
use App\Controllers\InmobiliariaController;
use App\Controllers\LandingController;
use App\Controllers\PublicarController;
use App\Controllers\SeoController;
use App\Controllers\VerificarController;
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
    $r->get('/aviso/{id:\d+}/denunciar', [DenunciarController::class, 'form']);
    $r->post('/aviso/{id:\d+}/denunciar', [DenunciarController::class, 'enviar']);
    $r->get('/aviso/{id:\d+}', fn (string $id) => (new AvisoController())->ver($id, ''));
    $r->get('/inmobiliaria/{slug:[a-z0-9-]+}', [InmobiliariaController::class, 'ver']);
    $r->get('/sitemap.xml', [SeoController::class, 'sitemap']);
    $r->get('/robots.txt', [SeoController::class, 'robots']);
    $r->get('/publicar', [PublicarController::class, 'form']);
    $r->post('/publicar', [PublicarController::class, 'enviar']);
    $r->get('/publicar/gracias', [PublicarController::class, 'gracias']);
    $r->get('/confirmar/{id:\d+}/{token:[a-f0-9]+}', [ConfirmarController::class, 'ver']);
    $r->post('/confirmar/{id:\d+}/{token:[a-f0-9]+}', [ConfirmarController::class, 'confirmar']);
    $r->get('/verificar/{id:\d+}/{token:[a-f0-9]+}', [VerificarController::class, 'ver']);
    $r->post('/verificar/{id:\d+}/{token:[a-f0-9]+}', [VerificarController::class, 'responder']);

    // Panel de administración
    $r->get('/admin/login', [Admin\AuthController::class, 'form']);
    $r->post('/admin/login', [Admin\AuthController::class, 'login']);
    $r->post('/admin/logout', [Admin\AuthController::class, 'logout']);
    $r->get('/admin', [Admin\AvisosController::class, 'inicio']);
    $r->get('/admin/avisos', [Admin\AvisosController::class, 'index']);
    $r->get('/admin/avisos/nuevo', [Admin\AvisosController::class, 'nuevo']);
    $r->post('/admin/avisos/nuevo', [Admin\AvisosController::class, 'crear']);
    $r->get('/admin/avisos/{id:\d+}', [Admin\AvisosController::class, 'editar']);
    $r->post('/admin/avisos/{id:\d+}', [Admin\AvisosController::class, 'guardar']);
    $r->post('/admin/avisos/{id:\d+}/estado', [Admin\AvisosController::class, 'estado']);
    $r->post('/admin/avisos/{id:\d+}/destacar', [Admin\AvisosController::class, 'destacar']);
    $r->post('/admin/avisos/{id:\d+}/fotos', [Admin\AvisosController::class, 'fotos']);
    $r->post('/admin/avisos/{id:\d+}/borrar', [Admin\AvisosController::class, 'borrar']);
    $r->get('/admin/moderacion', [Admin\ModeracionController::class, 'index']);
    $r->post('/admin/moderacion/{id:\d+}/aprobar', [Admin\ModeracionController::class, 'aprobar']);
    $r->post('/admin/moderacion/{id:\d+}/rechazar', [Admin\ModeracionController::class, 'rechazar']);
    $r->post('/admin/moderacion/{id:\d+}/bloquear', [Admin\ModeracionController::class, 'bloquear']);
    $r->get('/admin/denuncias', [Admin\DenunciasController::class, 'index']);
    $r->post('/admin/denuncias/{id:\d+}/{accion:descartar|alquilado|baja|bloquear}', [Admin\DenunciasController::class, 'resolver']);
    $r->get('/admin/bloqueos', [Admin\BloqueosController::class, 'index']);
    $r->post('/admin/bloqueos', [Admin\BloqueosController::class, 'crear']);
    $r->post('/admin/bloqueos/{id:\d+}/borrar', [Admin\BloqueosController::class, 'borrar']);
    $r->post('/admin/moderacion/{id:\d+}/confirmar-email', [Admin\ModeracionController::class, 'confirmarEmail']);
    $r->get('/admin/verificacion', [Admin\VerificacionController::class, 'index']);
    $r->post('/admin/verificacion/{id:\d+}/{accion:confirmar|solicitar|alquilado|pausar}', [Admin\VerificacionController::class, 'accion']);
    $r->get('/admin/inmobiliarias', [Admin\InmobiliariasController::class, 'index']);
    $r->get('/admin/inmobiliarias/nueva', [Admin\InmobiliariasController::class, 'nueva']);
    $r->post('/admin/inmobiliarias/nueva', [Admin\InmobiliariasController::class, 'crear']);
    $r->get('/admin/inmobiliarias/{id:\d+}', [Admin\InmobiliariasController::class, 'editar']);
    $r->post('/admin/inmobiliarias/{id:\d+}', [Admin\InmobiliariasController::class, 'guardar']);
    $r->post('/admin/inmobiliarias/{id:\d+}/borrar', [Admin\InmobiliariasController::class, 'borrar']);
    $r->get('/admin/barrios', [Admin\BarriosController::class, 'index']);
    $r->get('/admin/barrios/nuevo', [Admin\BarriosController::class, 'nuevo']);
    $r->post('/admin/barrios/nuevo', [Admin\BarriosController::class, 'crear']);
    $r->get('/admin/barrios/{id:\d+}', [Admin\BarriosController::class, 'editar']);
    $r->post('/admin/barrios/{id:\d+}', [Admin\BarriosController::class, 'guardar']);
    $r->post('/admin/barrios/{id:\d+}/borrar', [Admin\BarriosController::class, 'borrar']);
};
