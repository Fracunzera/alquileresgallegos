<?php

declare(strict_types=1);

namespace App\Services;

final class Slugger
{
    private const ACENTOS = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'â' => 'a', 'ê' => 'e',
        'î' => 'i', 'ô' => 'o', 'û' => 'u', 'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ç' => 'c',
    ];

    public static function slug(string $texto, int $max = 80): string
    {
        $texto = strtr(mb_strtolower($texto), self::ACENTOS);
        $texto = (string) preg_replace('/[^a-z0-9]+/', '-', $texto);
        $texto = trim($texto, '-');
        if (strlen($texto) > $max) {
            $corte = strrpos(substr($texto, 0, $max + 1), '-');
            $texto = rtrim(substr($texto, 0, $corte ?: $max), '-');
        }
        return $texto !== '' ? $texto : 'aviso';
    }
}
