<?php

declare(strict_types=1);

namespace Tests;

use App\Services\Extractor;
use PHPUnit\Framework\TestCase;

final class ExtractorTest extends TestCase
{
    private const BARRIOS = [
        ['id' => 1, 'nombre' => 'Centro'],
        ['id' => 5, 'nombre' => 'Fátima'],
        ['id' => 15, 'nombre' => 'Santa María de Belén'],
        ['id' => 9, 'nombre' => 'Bicentenario'],
    ];

    public function testPosteoTipicoDeFacebook(): void
    {
        $texto = "ALQUILO DEPTO 2 dormitorios en barrio Fátima!! 1 baño, cocina comedor.\n"
            . "Valor $ 650.000 + expensas $45.000. Requisitos: recibo de sueldo.\n"
            . "Interesados al WhatsApp 2966 15-123456";
        $d = Extractor::extraer($texto, self::BARRIOS);

        $this->assertSame('mensual', $d['tipo']);
        $this->assertSame('departamento', $d['categoria']);
        $this->assertSame(2, $d['dormitorios']);
        $this->assertSame(1, $d['banos']);
        $this->assertSame(650000, $d['precio']);
        $this->assertSame(45000, $d['expensas']);
        $this->assertSame('5492966123456', $d['contacto_whatsapp']);
        $this->assertSame(5, $d['barrio_id']);
    }

    public function testTemporarioPorDia(): void
    {
        $d = Extractor::extraer('Alquiler temporario por día, monoambiente equipado en Bicentenario. $45.000 la noche. Consultas 2966-555123', self::BARRIOS);
        $this->assertSame('temporario', $d['tipo']);
        $this->assertSame('dia', $d['periodo']);
        $this->assertSame('monoambiente', $d['categoria']);
        $this->assertSame(45000, $d['precio']);
        $this->assertSame(9, $d['barrio_id']);
        $this->assertSame('5492966555123', $d['contacto_whatsapp']);
    }

    public function testNombreDeBarrioMasLargoGana(): void
    {
        $d = Extractor::extraer('Casa en Santa María de Belén, 3 dormitorios, 500 mil por mes', self::BARRIOS);
        $this->assertSame(15, $d['barrio_id']);
        $this->assertSame(500000, $d['precio']);
        $this->assertSame('casa', $d['categoria']);
        $this->assertSame(3, $d['dormitorios']);
    }

    public function testSinExpensasYDolares(): void
    {
        $d = Extractor::extraer('Local comercial céntrico USD 800, sin expensas. info@inmo.com.ar', self::BARRIOS);
        $this->assertSame('local', $d['categoria']);
        $this->assertSame('USD', $d['moneda']);
        $this->assertSame(800, $d['precio']);
        $this->assertSame(0, $d['expensas']);
        $this->assertSame('info@inmo.com.ar', $d['contacto_email']);
        $this->assertArrayNotHasKey('contacto_whatsapp', $d);
    }

    /** @return array<string, array{string, ?string, ?string}> */
    public static function casosMascotasYChicos(): array
    {
        return [
            'acepta ambos' => ['Depto amplio, se aceptan mascotas y se aceptan niños', 'si', 'si'],
            'no acepta mascotas' => ['Casa con patio. NO se aceptan mascotas. Apto familias', 'no', 'si'],
            'sin animales ni chicos' => ['Monoambiente sin mascotas, sin niños', 'no', 'no'],
            'a consultar' => ['Mascotas a consultar. Chicos: sí', 'consultar', 'si'],
            'pet friendly y solo adultos' => ['Pet friendly! Solo adultos', 'si', 'no'],
            'no acepta perros' => ['No acepta perros. Ideal pareja', 'no', null],
            'no dice nada' => ['Departamento 2 dormitorios con cochera', null, null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('casosMascotasYChicos')]
    public function testMascotasYChicos(string $texto, ?string $mascotas, ?string $chicos): void
    {
        $d = Extractor::extraer($texto);
        $this->assertSame($mascotas, $d['acepta_mascotas'] ?? null, 'mascotas');
        $this->assertSame($chicos, $d['acepta_chicos'] ?? null, 'chicos');
    }
}
