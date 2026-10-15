<?php

declare(strict_types=1);

namespace Tests;

use App\Services\Slugger;
use PHPUnit\Framework\TestCase;

final class SluggerTest extends TestCase
{
    public function testQuitaAcentosYSimbolos(): void
    {
        $this->assertSame(
            'departamento-2-ambientes-en-barrio-fatima-con-cochera',
            Slugger::slug('¡Departamento 2 ambientes en Barrio Fátima, con cochera!')
        );
        $this->assertSame('cabana-nandu', Slugger::slug('Cabaña Ñandú'));
    }

    public function testCortaEnLimiteDePalabra(): void
    {
        $slug = Slugger::slug('casa amplia con patio y quincho cerca del centro y de la costanera', 30);
        $this->assertLessThanOrEqual(30, strlen($slug));
        $this->assertSame('casa-amplia-con-patio-y', $slug);
    }

    public function testNuncaDevuelveVacio(): void
    {
        $this->assertSame('aviso', Slugger::slug('¿¿??'));
    }
}
