<?php

declare(strict_types=1);

namespace App;

/**
 * Lee y valida los filtros del querystring. Lo inválido se ignora en silencio.
 * Cualquier filtro activo convierte la página en noindex (evita contenido duplicado).
 */
final class Filtros
{
    public const ORDENES = [
        'recientes' => 'Más recientes',
        'precio_asc' => 'Menor precio',
        'precio_desc' => 'Mayor precio',
    ];

    /**
     * @param array<string, mixed> $fijos filtros que impone la landing (no se pueden cambiar)
     * @param list<array<string, mixed>> $barrios barrios activos
     * @return array{filtros: array<string, mixed>, query: array<string, string>, activos: bool}
     */
    public static function desdeQuery(array $fijos, array $barrios): array
    {
        $filtros = [];
        $query = [];

        $tipo = Http::query('tipo');
        if (!isset($fijos['tipo']) && $tipo !== null && isset(Catalogo::TIPOS[$tipo])) {
            $filtros['tipo'] = $query['tipo'] = $tipo;
        }
        $categoria = Http::query('categoria');
        if (!isset($fijos['categoria']) && $categoria !== null && isset(Catalogo::CATEGORIAS[$categoria])) {
            $filtros['categoria'] = $query['categoria'] = $categoria;
        }
        $barrio = Http::query('barrio');
        if (!isset($fijos['barrio_id']) && $barrio !== null && $barrio !== '') {
            foreach ($barrios as $b) {
                if ($b['slug'] === $barrio) {
                    $filtros['barrio_id'] = (int) $b['id'];
                    $query['barrio'] = $barrio;
                }
            }
        }
        $dorm = Http::query('dormitorios');
        if ($dorm !== null && ctype_digit($dorm) && (int) $dorm >= 1 && (int) $dorm <= 5) {
            $filtros['dorm_min'] = (int) $dorm;
            $query['dormitorios'] = $dorm;
        }
        $precio = preg_replace('/\D/', '', (string) Http::query('precio_max'));
        if ($precio !== '' && (int) $precio > 0) {
            $filtros['precio_max'] = (int) $precio;
            $query['precio_max'] = $precio;
        }
        foreach (['mascotas' => 'acepta_mascotas', 'ninos' => 'acepta_ninos'] as $param => $campo) {
            if (!isset($fijos[$campo]) && Http::query($param) === '1') {
                $filtros[$campo] = 'si';
                $query[$param] = '1';
            }
        }
        $orden = Http::query('orden');
        if ($orden !== null && $orden !== 'recientes' && isset(self::ORDENES[$orden])) {
            $filtros['orden'] = $query['orden'] = $orden;
        }

        return ['filtros' => $filtros, 'query' => $query, 'activos' => $query !== []];
    }

    /**
     * Página pedida. Devuelve null si el parámetro es inválido (el controlador redirige).
     */
    public static function pagina(): ?int
    {
        $pagina = Http::query('pagina');
        if ($pagina === null) {
            return 1;
        }
        return ctype_digit($pagina) && (int) $pagina >= 2 ? (int) $pagina : null;
    }
}
