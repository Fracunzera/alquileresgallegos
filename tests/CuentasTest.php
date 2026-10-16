<?php

declare(strict_types=1);

namespace Tests;

use App\Services\Cuentas;
use PHPUnit\Framework\TestCase;

final class CuentasTest extends TestCase
{
    public function testValidarClave(): void
    {
        $this->assertNull(Cuentas::validarClave('unaClaveLarga', 'unaClaveLarga'));
        $this->assertStringContainsString('al menos 8', (string) Cuentas::validarClave('corta', 'corta'));
        $this->assertStringContainsString('no coinciden', (string) Cuentas::validarClave('unaClaveLarga', 'otraClaveLarga'));
        $this->assertStringContainsString('demasiado larga', (string) Cuentas::validarClave(str_repeat('a', 73), str_repeat('a', 73)));
        $this->assertNull(Cuentas::validarClave('ñandú-río-2026', 'ñandú-río-2026'), 'acentos y ñ valen');
    }

    public function testRegistroExigeClave(): void
    {
        [, $err] = Cuentas::validarRegistro(['nombre' => 'Ana', 'email' => 'ana@example.com', 'acepto' => '1', 'clave' => 'abc', 'clave_repetida' => 'abc']);
        $this->assertArrayHasKey('clave', $err);
        [$datos, $err] = Cuentas::validarRegistro(['nombre' => 'Ana', 'email' => 'ana@example.com', 'acepto' => '1', 'clave' => 'claveSegura1', 'clave_repetida' => 'claveSegura1']);
        $this->assertSame([], $err);
        $this->assertSame('claveSegura1', $datos['clave']);
    }
}
