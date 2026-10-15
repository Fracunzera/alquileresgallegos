<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Catalogo;
use App\Http;
use App\NoEncontrado;
use App\Repositories\AvisoRepo;
use App\Seo\Meta;
use App\Services\Auth;
use App\View;

final class AvisoController
{
    public function ver(string $id, string $slug): string
    {
        $repo = new AvisoRepo();
        $aviso = $repo->porId((int) $id) ?? throw new NoEncontrado();

        // Pendientes y rechazados no existen para el público (el admin puede previsualizarlos).
        $visible = in_array($aviso['estado'], ['activo', 'pausado', 'alquilado'], true);
        if (!$visible && !Auth::estaLogueado()) {
            throw new NoEncontrado();
        }

        $url = Catalogo::urlAviso((int) $aviso['id'], $aviso['slug']);
        if ($slug !== $aviso['slug']) {
            Http::redirigir($url, 301);
        }

        $fotos = $repo->fotos((int) $aviso['id']);
        $descripcionCorta = Catalogo::descripcionCorta($aviso);
        $datos = array_filter([Catalogo::precio($aviso), Catalogo::expensas($aviso), Catalogo::ambientes($aviso)]);

        $meta = new Meta(
            title: $aviso['titulo'] . ' | ' . $descripcionCorta . ', Río Gallegos',
            description: implode('. ', $datos) . '. ' . $aviso['descripcion'],
            canonical: $url,
            indexar: $aviso['estado'] === 'activo',
            imagen: $fotos ? Catalogo::urlFoto($fotos[0]['archivo'], 1200) : null,
            ogTipo: 'article',
        );

        return View::render('aviso', [
            'meta' => $meta,
            'aviso' => $aviso,
            'fotos' => $fotos,
            'url' => $url,
            'similares' => $repo->similares($aviso),
            'mapa' => self::mapa($aviso),
            'migas' => self::migas($aviso),
            'conBarraContacto' => $aviso['estado'] === 'activo',
        ]);
    }

    /**
     * Coordenadas para el mapa. Si la dirección no es pública se redondean a 3 decimales
     * (~100 m) y se muestra un círculo: la ubicación exacta nunca llega al HTML.
     *
     * @param array<string, mixed> $aviso
     * @return array{lat: float, lng: float, aproximado: bool}|null
     */
    private static function mapa(array $aviso): ?array
    {
        if ($aviso['lat'] === null || $aviso['lng'] === null) {
            return null;
        }
        $aproximado = !$aviso['mostrar_direccion'];
        $decimales = $aproximado ? 3 : 6;
        return [
            'lat' => round((float) $aviso['lat'], $decimales),
            'lng' => round((float) $aviso['lng'], $decimales),
            'aproximado' => $aproximado,
        ];
    }

    /** @param array<string, mixed> $aviso @return list<array{0: string, 1: ?string}> */
    private static function migas(array $aviso): array
    {
        $migas = [['Inicio', '/']];
        $migas[] = $aviso['tipo'] === 'temporario'
            ? ['Alquiler temporario', '/alquiler-temporario-rio-gallegos']
            : ['Alquileres mensuales', '/alquileres-mensuales-rio-gallegos'];
        if ($aviso['barrio_slug']) {
            $migas[] = [$aviso['barrio_nombre'], Catalogo::urlBarrio($aviso['barrio_slug'])];
        }
        $migas[] = [$aviso['titulo'], null];
        return $migas;
    }
}
