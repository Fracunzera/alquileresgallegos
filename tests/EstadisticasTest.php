<?php

declare(strict_types=1);

namespace Tests;

use App\Services\Estadisticas;
use PHPUnit\Framework\TestCase;

final class EstadisticasTest extends TestCase
{
    public function testDetectaBots(): void
    {
        $this->assertTrue(Estadisticas::esBot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
        $this->assertTrue(Estadisticas::esBot('WhatsApp/2.23.20.0'));
        $this->assertTrue(Estadisticas::esBot('facebookexternalhit/1.1'));
        $this->assertTrue(Estadisticas::esBot('Mozilla/5.0 HeadlessChrome/120.0'));
        $this->assertTrue(Estadisticas::esBot(''));
        $this->assertFalse(Estadisticas::esBot('Mozilla/5.0 (Linux; Android 14; SM-A145M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Mobile Safari/537.36'));
        $this->assertFalse(Estadisticas::esBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'));
    }
}
