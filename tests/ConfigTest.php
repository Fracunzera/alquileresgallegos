<?php

declare(strict_types=1);

namespace Tests;

use App\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testParseaComillasYComentarios(): void
    {
        $valores = Config::parsear(<<<'ENV'
            # comentario
            A=1            # comentario al final
            B="Alquileres Río Gallegos"
            C='&copy; OSM # no es comentario'
            D=
            E=               # vacío con comentario
            F=valor#sin-espacio
            ENV);
        $this->assertSame([
            'A' => '1',
            'B' => 'Alquileres Río Gallegos',
            'C' => '&copy; OSM # no es comentario',
            'D' => '',
            'E' => '',
            'F' => 'valor#sin-espacio',
        ], $valores);
    }
}
