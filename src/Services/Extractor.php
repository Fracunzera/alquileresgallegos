<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Saca datos estructurados de un texto pegado (posteo de Facebook, web de inmobiliaria).
 * Es una ayuda para la carga manual: solo propone valores, el admin revisa antes de guardar.
 */
final class Extractor
{
    /**
     * @param list<array{id: int|string, nombre: string}> $barrios
     * @return array<string, string|int> campos detectados (solo los que encontró)
     */
    public static function extraer(string $texto, array $barrios = []): array
    {
        $t = mb_strtolower($texto);
        $plano = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $d = [];

        if (preg_match('/\b(por (dia|noche|semana)|temporari[oa]s?|diari[oa]|x dia|x noche)\b/u', $plano)) {
            $d['tipo'] = 'temporario';
            $d['periodo'] = 'dia';
        } elseif (preg_match('/\b(mensual|por mes|x mes|alquil(o|a|amos|er))\b/u', $plano)) {
            $d['tipo'] = 'mensual';
            $d['periodo'] = 'mes';
        }

        foreach ([
            'monoambiente' => '/\bmono ?ambiente\b/',
            'habitacion' => '/\b(habitacion|pieza) (en alquiler|para alquilar|amueblada|compartida|individual)|\balquilo (habitacion|pieza)\b/',
            'local' => '/\blocal( comercial)?\b/',
            'ph' => '/\bph\b/',
            'departamento' => '/\b(departamento|depto|dpto|depa)\b/',
            'casa' => '/\b(casa|chalet|duplex)\b/',
        ] as $categoria => $regex) {
            if (preg_match($regex, $plano)) {
                $d['categoria'] = $categoria;
                break;
            }
        }

        if (preg_match('/(\d{1,2})\s*(dormitorios?|dorm\b|habitaciones|hab\b)/', $plano, $m)) {
            $d['dormitorios'] = (int) $m[1];
        } elseif (preg_match('/\b(un|una|dos|tres|cuatro)\s+(dormitorios?|habitaciones)/', $plano, $m)) {
            $d['dormitorios'] = ['un' => 1, 'una' => 1, 'dos' => 2, 'tres' => 3, 'cuatro' => 4][$m[1]];
        }
        if (preg_match('/(\d{1,2})\s*(ambientes?|amb\b)/', $plano, $m)) {
            $d['ambientes'] = (int) $m[1];
        }
        if (preg_match('/(\d)\s*banos?\b/', $plano, $m)) {
            $d['banos'] = (int) $m[1];
        }

        if (preg_match('/\b(usd|u\$s|us\$|dolares)\b/', $plano)) {
            $d['moneda'] = 'USD';
        }

        // Expensas primero, para no confundirlas con el precio.
        $sinExpensas = $plano;
        if (preg_match('/expensas?\s*(?:de|:)?\s*\$?\s*(\d{1,3}(?:[.\s]\d{3})+|\d{4,})/', $plano, $m)) {
            $d['expensas'] = (int) preg_replace('/\D/', '', $m[1]);
            $sinExpensas = str_replace($m[0], ' ', $plano);
        } elseif (preg_match('/(sin expensas|no (tiene|paga) expensas)/', $plano)) {
            $d['expensas'] = 0;
        }
        if (preg_match('/(?:\$|usd|u\$s|us\$|precio:?|valor:?)\s*(\d{1,3}(?:[.\s]\d{3})+|\d{3,})/', $sinExpensas, $m)) {
            $d['precio'] = (int) preg_replace('/\D/', '', $m[1]);
        } elseif (preg_match('/\b(\d{2,4})\s*(mil|k)\b/', $sinExpensas, $m)) {
            $d['precio'] = (int) $m[1] * 1000;
        }

        $mascotas = self::acepta(
            $plano,
            '(mascotas?|animales|perros?|gatos?)',
            '/\b(pet ?friendly|apto mascotas?)\b/'
        );
        if ($mascotas !== null) {
            $d['acepta_mascotas'] = $mascotas;
        }
        // En los posteos se escribe de mil formas (niños, chicos, menores, hijos): todas van a "niños".
        $ninos = self::acepta(
            $plano,
            '(ninos|chicos|menores|criaturas|nenes|hijos)',
            '/\b(apto (para )?(familias|chicos|ninos))\b/',
            '/\b(solo adultos|sin hijos|pareja sin hijos)\b/'
        );
        if ($ninos !== null) {
            $d['acepta_ninos'] = $ninos;
        }

        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/', $t, $m)) {
            $d['contacto_email'] = $m[0];
        }
        // Teléfonos: secuencias de dígitos con separadores, de 6 a 15 dígitos.
        if (preg_match_all('/\+?\(?\d[\d\s\-().]{5,22}\d/', $texto, $m)) {
            foreach ($m[0] as $candidato) {
                $digitos = preg_replace('/\D/', '', $candidato);
                if (strlen($digitos) < 6 || (isset($d['precio']) && (int) $digitos === $d['precio'])) {
                    continue;
                }
                $wa = Telefono::whatsapp($candidato);
                if ($wa !== null && (strlen($digitos) >= 8 || preg_match('/(whats|wsp|wpp|cel|tel|llamar|contacto)/', $plano))) {
                    $d['contacto_whatsapp'] = $wa;
                    break;
                }
            }
        }

        // Barrio: el nombre más largo que aparezca (así "Santa María de Belén" gana sobre "Belén").
        usort($barrios, fn ($a, $b) => mb_strlen($b['nombre']) <=> mb_strlen($a['nombre']));
        // Nombres cortos (YPF, SUPE, Evita) solo cuentan con "barrio" adelante, para evitar falsos positivos.
        foreach ($barrios as $barrio) {
            $nombre = preg_quote(strtr(mb_strtolower($barrio['nombre']), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']), '/');
            $regex = mb_strlen($barrio['nombre']) >= 6 ? "/\\b{$nombre}\\b/u" : "/\\b(barrio|b°|bº)\\s*{$nombre}\\b/u";
            if (preg_match($regex, $plano)) {
                $d['barrio_id'] = (int) $barrio['id'];
                break;
            }
        }

        return $d;
    }

    /**
     * "no se aceptan mascotas" → no; "mascotas a consultar" → consultar; "se aceptan mascotas" → si.
     * Se busca primero la negación para que "no acepta mascotas" no se lea como "acepta mascotas".
     */
    private static function acepta(string $plano, string $sujeto, string $siExtra, ?string $noExtra = null): ?string
    {
        $negacion = '/\b(no se (aceptan|permiten|admiten)|no (acepta|aceptamos|aceptan|permite|permitimos|se aceptan)|sin)\s+' . $sujeto . '\b'
            . '|\b' . $sujeto . ':?\s*(no|prohibid[oa]s?|no se aceptan)\b/';
        if (preg_match($negacion, $plano) || ($noExtra !== null && preg_match($noExtra, $plano))) {
            return 'no';
        }
        if (preg_match('/\b' . $sujeto . ':?\s*(a consultar|consultar)\b/', $plano)) {
            return 'consultar';
        }
        $afirmacion = '/\b(se (aceptan|permiten|admiten)|acepta(mos|n)?|permite(n)?|permitimos)\s+' . $sujeto . '\b'
            . '|\b' . $sujeto . ':?\s*(si|permitid[oa]s|bienvenid[oa]s)\b/';
        if (preg_match($afirmacion, $plano) || preg_match($siExtra, $plano)) {
            return 'si';
        }
        return null;
    }
}
