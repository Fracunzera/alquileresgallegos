<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Catalogo;
use App\Filtros;
use App\Http;
use App\NoEncontrado;
use App\Repositories\AvisoRepo;
use App\Repositories\InmobiliariaRepo;
use App\Seo\JsonLd;
use App\Seo\Meta;
use App\View;

final class InmobiliariaController
{
    public function ver(string $slug): string
    {
        $inmo = (new InmobiliariaRepo())->porSlug($slug) ?? throw new NoEncontrado();
        $path = Catalogo::urlInmobiliaria($slug);
        $pagina = Filtros::pagina() ?? Http::redirigir($path, 301);

        $resultado = (new AvisoRepo())->buscar(['inmobiliaria_id' => (int) $inmo['id']], $pagina);
        $ultima = max(1, (int) ceil($resultado['total'] / AvisoRepo::POR_PAGINA));
        if ($pagina > $ultima) {
            throw new NoEncontrado();
        }

        $description = $inmo['descripcion']
            ?: "{$inmo['nombre']}: {$resultado['total']} propiedades en alquiler en Río Gallegos. Teléfono, WhatsApp y todos sus avisos de alquiler mensual y temporario.";

        return View::render('inmobiliaria', [
            'meta' => new Meta(
                title: "{$inmo['nombre']}: alquileres en Río Gallegos" . ($pagina > 1 ? " (página {$pagina})" : ''),
                description: $description,
                canonical: url_con($path, ['pagina' => $pagina > 1 ? $pagina : null]),
                indexar: $resultado['total'] > 0,
                prev: $pagina > 1 ? url_con($path, ['pagina' => $pagina > 2 ? $pagina - 1 : null]) : null,
                next: $pagina < $ultima ? url_con($path, ['pagina' => $pagina + 1]) : null,
                imagen: $inmo['logo'] ?: null,
                jsonLd: [
                    JsonLd::inmobiliaria($inmo),
                    JsonLd::migas([['Inicio', '/'], [$inmo['nombre'], null]], $path),
                    JsonLd::listado('Propiedades en alquiler de ' . $inmo['nombre'], $resultado['items'], ($pagina - 1) * AvisoRepo::POR_PAGINA),
                ],
            ),
            'inmo' => $inmo,
            'items' => $resultado['items'],
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'ultima' => $ultima,
            'path' => $path,
            'migas' => [['Inicio', '/'], [$inmo['nombre'], null]],
        ]);
    }
}
