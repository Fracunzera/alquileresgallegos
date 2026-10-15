<?php

declare(strict_types=1);

namespace Tests;

use App\Services\Telefono;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TelefonoTest extends TestCase
{
    /** @return array<string, array{string, ?string}> */
    public static function casos(): array
    {
        return [
            'internacional completo' => ['+54 9 2966 123456', '5492966123456'],
            'con 0 y 15' => ['(02966) 15-123456', '5492966123456'],
            'área y 15 sin 0' => ['2966 15 123456', '5492966123456'],
            'área sin 15' => ['2966123456', '5492966123456'],
            'solo 15 local' => ['15-123456', '5492966123456'],
            'fijo local' => ['423456', '5492966423456'],
            '54 sin 9' => ['54 2966 123456', '5492966123456'],
            'Buenos Aires con 15' => ['011 15 1234 5678', '5491112345678'],
            'Buenos Aires internacional' => ['+54 9 11 1234-5678', '5491112345678'],
            'basura' => ['abc', null],
            'muy largo' => ['1234567890123456', null],
        ];
    }

    #[DataProvider('casos')]
    public function testNormalizaParaWhatsapp(string $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, Telefono::whatsapp($entrada));
    }
}
