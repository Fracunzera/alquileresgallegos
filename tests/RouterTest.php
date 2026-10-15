<?php

declare(strict_types=1);

namespace Tests;

use App\MetodoNoPermitido;
use App\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testRutasFijasGananSobreLasDePatron(): void
    {
        $r = new Router();
        $r->get('/alquileres-mensuales-rio-gallegos', fn () => 'mensuales');
        $r->get('/alquileres-{barrio:[a-z0-9-]+}-rio-gallegos', fn (string $barrio) => "barrio:$barrio");

        $this->assertSame('mensuales', $r->despachar('GET', '/alquileres-mensuales-rio-gallegos'));
        $this->assertSame('barrio:22-de-septiembre', $r->despachar('GET', '/alquileres-22-de-septiembre-rio-gallegos'));
    }

    public function testParametrosDelAviso(): void
    {
        $r = new Router();
        $r->get('/aviso/{id:\d+}-{slug:[a-z0-9-]*}', fn (string $id, string $slug) => "$id|$slug");
        $this->assertSame('15|depto-centro', $r->despachar('GET', '/aviso/15-depto-centro'));
        $this->assertNull($r->resolver('GET', '/aviso/abc-depto'));
    }

    public function testMetodoNoPermitido(): void
    {
        $r = new Router();
        $r->post('/publicar', fn () => 'ok');
        $this->expectException(MetodoNoPermitido::class);
        $r->resolver('GET', '/publicar');
    }
}
