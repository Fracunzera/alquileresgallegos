<?php

declare(strict_types=1);

namespace Tests;

use App\Config;
use App\Seo\JsonLd;
use App\Seo\Meta;
use PHPUnit\Framework\TestCase;

final class SeoTest extends TestCase
{
    protected function setUp(): void
    {
        Config::set('APP_URL', 'https://www.ejemplo.com.ar');
        Config::set('APP_NOMBRE', 'Alquileres Río Gallegos');
    }

    public function testMarcaSoloSiEntra(): void
    {
        $this->assertSame('Casas en alquiler | Alquileres Río Gallegos', Meta::conMarca('Casas en alquiler'));
        $largo = 'Departamento de 3 dormitorios con cochera y patio en Barrio Centro';
        $this->assertSame($largo, Meta::conMarca($largo));
    }

    public function testDescripcionSeRecortaEnPalabra(): void
    {
        $meta = new Meta('t', str_repeat('palabra ', 40));
        $this->assertLessThanOrEqual(158, mb_strlen($meta->description));
        $this->assertStringEndsWith('palabra…', $meta->description);
    }

    public function testJsonLdDeAvisoSinPrecioNiDireccionPublica(): void
    {
        $aviso = [
            'id' => 7, 'slug' => 'casa-con-patio', 'titulo' => 'Casa con patio', 'descripcion' => "Linda\n\ncasa",
            'categoria' => 'casa', 'tipo' => 'mensual', 'ambientes' => 4, 'dormitorios' => 2, 'banos' => null,
            'direccion' => 'Calle Falsa 123', 'mostrar_direccion' => 0, 'barrio_nombre' => 'Fátima',
            'precio' => null, 'moneda' => 'ARS', 'periodo' => 'mes', 'expensas' => null, 'estado' => 'activo',
            'inmobiliaria_slug' => null, 'inmobiliaria_nombre' => null,
            'publicado_en' => '2026-10-01 10:00:00', 'creado_en' => '2026-10-01 10:00:00', 'actualizado_en' => '2026-10-02 10:00:00',
        ];
        $ld = JsonLd::aviso($aviso, [], ['lat' => -51.62, 'lng' => -69.21, 'aproximado' => true]);

        $this->assertSame('RealEstateListing', $ld['@type']);
        $this->assertSame('https://www.ejemplo.com.ar/aviso/7-casa-con-patio', $ld['url']);
        $this->assertSame('SingleFamilyResidence', $ld['about']['@type']);
        $this->assertArrayNotHasKey('streetAddress', $ld['about']['address'], 'la dirección oculta no se publica');
        $this->assertArrayNotHasKey('numberOfBathroomsTotal', $ld['about']);
        $this->assertArrayNotHasKey('price', $ld['offers']);
        $this->assertArrayNotHasKey('image', $ld);
        $this->assertSame('Linda casa', $ld['description']);
        $this->assertNotFalse(json_encode($ld));
    }
}
