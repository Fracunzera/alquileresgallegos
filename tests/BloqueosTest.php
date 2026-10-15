<?php

declare(strict_types=1);

namespace Tests;

use App\Services\Bloqueos;
use PHPUnit\Framework\TestCase;

final class BloqueosTest extends TestCase
{
    public function testNormalizaCadaTipo(): void
    {
        $this->assertSame('5492966123456', Bloqueos::normalizar('whatsapp', '(02966) 15-123456'));
        $this->assertSame('ana@example.com', Bloqueos::normalizar('email', ' Ana@Example.COM '));
        $this->assertSame('2001:db8::1', Bloqueos::normalizar('ip', '2001:0db8:0000::0001'));
        $this->assertSame('190.2.3.4', Bloqueos::normalizar('ip', '190.2.3.4'));
        $this->assertNull(Bloqueos::normalizar('ip', '999.1.1.1'));
        $this->assertNull(Bloqueos::normalizar('email', 'no-es-email'));
        $this->assertNull(Bloqueos::normalizar('whatsapp', ''));
    }
}
