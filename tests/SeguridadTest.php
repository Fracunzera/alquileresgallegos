<?php

declare(strict_types=1);

namespace Tests;

use App\Config;
use App\Http;
use App\Seguridad;
use PHPUnit\Framework\TestCase;

final class SeguridadTest extends TestCase
{
    protected function tearDown(): void
    {
        Http::reiniciarIp();
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP']);
        Config::set('PROXIES_CONFIABLES', '');
    }

    public function testPathsQueTerminanEnRedireccionAOtroDominio(): void
    {
        $this->assertTrue(Seguridad::pathPeligroso('/\\evil.com/'));
        $this->assertTrue(Seguridad::pathPeligroso('//evil.com/'));
        $this->assertTrue(Seguridad::pathPeligroso("/aviso\0"));
        $this->assertFalse(Seguridad::pathPeligroso('/casas-alquiler-rio-gallegos/'));
    }

    public function testCspSoloPermiteElScriptInlinePorHash(): void
    {
        Config::set('MAPA_TILES_URL', 'https://{s}.tile.example.org/{z}/{x}/{y}.png');
        $csp = Seguridad::csp();
        $this->assertStringContainsString("script-src 'self' 'sha256-6LnkgnIPlhyZsn0oGQpKqWoK9BCAGCzrYNCK+Zc1zO4='", $csp);
        $this->assertStringNotContainsString("'unsafe-inline' 'unsafe-eval'", $csp);
        $this->assertStringContainsString('img-src \'self\' data: https://*.tile.example.org', $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    public function testIpSinProxyConfiableIgnoraCabeceras(): void
    {
        $_SERVER['REMOTE_ADDR'] = '200.1.1.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
        $this->assertSame('200.1.1.1', Http::ip(), 'sin proxy configurado, X-Forwarded-For se puede falsificar');
    }

    public function testIpDetrasDeProxyConfiable(): void
    {
        Config::set('PROXIES_CONFIABLES', '10.0.0.0/8, 2001:db8::/32');
        Config::set('IP_CABECERA', 'X-Forwarded-For');
        $_SERVER['REMOTE_ADDR'] = '10.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6, 190.5.5.5, 10.0.0.7';
        $this->assertSame('190.5.5.5', Http::ip(), 'la primera IP no confiable desde la derecha');
    }

    public function testRangosCidr(): void
    {
        $this->assertTrue(Http::ipEnRangos('173.245.48.10', ['173.245.48.0/20']));
        $this->assertFalse(Http::ipEnRangos('173.245.64.1', ['173.245.48.0/20']));
        $this->assertTrue(Http::ipEnRangos('2400:cb00::1', ['2400:cb00::/32']));
        $this->assertFalse(Http::ipEnRangos('190.1.1.1', ['2400:cb00::/32']));
        $this->assertTrue(Http::ipEnRangos('127.0.0.1', ['127.0.0.1']));
    }
}
