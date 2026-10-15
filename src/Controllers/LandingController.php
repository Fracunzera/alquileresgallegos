<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Catalogo;
use App\Filtros;
use App\Http;
use App\NoEncontrado;
use App\Repositories\AvisoRepo;
use App\Repositories\BarrioRepo;
use App\Seo\JsonLd;
use App\Seo\Landings;
use App\Seo\Meta;
use App\View;

final class LandingController
{
    private AvisoRepo $avisos;
    private BarrioRepo $barrios;

    public function __construct()
    {
        $this->avisos = new AvisoRepo();
        $this->barrios = new BarrioRepo();
    }

    public function fija(string $path): string
    {
        return $this->listar($path, Landings::fijas()[$path] ?? throw new NoEncontrado());
    }

    public function barrio(string $slug): string
    {
        $barrio = $this->barrios->porSlug($slug) ?? throw new NoEncontrado();
        return $this->listar(Catalogo::urlBarrio($slug), Landings::barrio($barrio), $barrio);
    }

    /** Buscador genérico: nunca se indexa; si la búsqueda equivale a una landing, redirige ahí. */
    public function buscar(): string
    {
        $q = Filtros::desdeQuery([], $this->barrios->activos());
        if (count($q['query']) === 1) {
            $destino = match (array_key_first($q['query'])) {
                'tipo' => $q['query']['tipo'] === 'mensual' ? '/alquileres-mensuales-rio-gallegos' : '/alquiler-temporario-rio-gallegos',
                'categoria' => [
                    'departamento' => '/departamentos-alquiler-rio-gallegos',
                    'casa' => '/casas-alquiler-rio-gallegos',
                    'monoambiente' => '/monoambientes-rio-gallegos',
                ][$q['query']['categoria']] ?? null,
                'barrio' => Catalogo::urlBarrio($q['query']['barrio']),
                default => null,
            };
            if ($destino !== null) {
                Http::redirigir($destino, 301);
            }
        }
        if (!$q['activos']) {
            Http::redirigir('/', 302);
        }

        return $this->listar('/buscar', [
            'filtro' => [],
            'h1' => 'Buscar alquileres en Río Gallegos',
            'title' => 'Buscar alquileres en Río Gallegos',
            'description' => 'Resultados de búsqueda de alquileres en Río Gallegos.',
            'intro' => null,
            'periodo' => 'mes',
            'buscador' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $def
     * @param array<string, mixed>|null $barrio
     */
    private function listar(string $path, array $def, ?array $barrio = null): string
    {
        $barrios = $this->barrios->activos();
        $q = Filtros::desdeQuery($def['filtro'], $barrios);
        $pagina = Filtros::pagina() ?? Http::redirigir(url_con($path, $q['query']), 301);

        $resultado = $this->avisos->buscar($def['filtro'] + $q['filtros'], $pagina);
        $ultima = max(1, (int) ceil($resultado['total'] / AvisoRepo::POR_PAGINA));
        if ($pagina > $ultima) {
            throw new NoEncontrado();
        }

        $stats = $this->avisos->estadisticas($def['filtro'], $def['periodo']);
        $buscador = !empty($def['buscador']);
        if ($stats['total'] > 0 && !$buscador) {
            $title = Landings::completar($def['title'], $stats, $def['periodo']);
            $description = Landings::completar($def['description'], $stats, $def['periodo']);
        } else {
            $title = $def['h1'];
            $description = $def['intro'] ?? $def['description'];
        }
        if ($pagina > 1) {
            $title .= " (página {$pagina})";
            $description = "Página {$pagina}. " . $description;
        }

        $migas = [['Inicio', '/']];
        if ($barrio !== null) {
            $migas[] = ['Barrios', '/#barrios'];
        }
        $migas[] = [$def['h1'], null];

        $meta = new Meta(
            title: $title,
            description: $description,
            canonical: $q['activos'] || $buscador ? $path : url_con($path, ['pagina' => $pagina > 1 ? $pagina : null]),
            indexar: !$q['activos'] && !$buscador && $stats['total'] > 0,
            prev: $pagina > 1 ? url_con($path, $q['query'] + ['pagina' => $pagina > 2 ? $pagina - 1 : null]) : null,
            next: $pagina < $ultima ? url_con($path, $q['query'] + ['pagina' => $pagina + 1]) : null,
            jsonLd: [
                JsonLd::migas($migas, $path),
                JsonLd::listado($def['h1'], $resultado['items'], ($pagina - 1) * AvisoRepo::POR_PAGINA),
            ],
        );


        return View::render('landing', [
            'meta' => $meta,
            'def' => $def,
            'path' => $path,
            'barrio' => $barrio,
            'items' => $resultado['items'],
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'ultima' => $ultima,
            'query' => $q['query'],
            'barrios' => $barrios,
            'migas' => $migas,
        ]);
    }
}
