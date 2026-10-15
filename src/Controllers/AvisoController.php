<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Catalogo;
use App\Http;
use App\NoEncontrado;
use App\Repositories\AvisoRepo;
use App\Seo\JsonLd;
use App\Seo\Meta;
use App\Services\Fotos;
use App\Services\Auth;
use App\Services\CuentaAuth;
use App\View;

final class AvisoController
{
    public function ver(string $id, string $slug): string
    {
        $repo = new AvisoRepo();
        $aviso = $repo->porId((int) $id) ?? throw new NoEncontrado();

        // Pendientes y rechazados no existen para el público (el admin puede previsualizarlos).
        $visible = in_array($aviso['estado'], ['activo', 'pausado', 'alquilado'], true);
        $esDuenio = $aviso['cuenta_id'] !== null && (int) (CuentaAuth::cuenta()['id'] ?? 0) === (int) $aviso['cuenta_id'];
        if (!$visible && !Auth::estaLogueado() && !$esDuenio) {
            throw new NoEncontrado();
        }

        $url = Catalogo::urlAviso((int) $aviso['id'], $aviso['slug']);
        if ($slug !== $aviso['slug']) {
            Http::redirigir($url, 301);
        }

        $fotos = $repo->fotos((int) $aviso['id']);
        $descripcionCorta = Catalogo::descripcionCorta($aviso);
        $datos = array_filter([Catalogo::precio($aviso), Catalogo::expensas($aviso), Catalogo::ambientes($aviso)]);

        $mapa = self::mapa($aviso);
        $migas = self::migas($aviso);
        $og = $fotos ? Fotos::urlOg($fotos[0]['archivo']) : null;

        $meta = new Meta(
            title: $aviso['titulo'] . ' – ' . ($aviso['tipo'] === 'temporario' ? 'Alquiler temporario' : 'Alquiler')
                . ($aviso['barrio_nombre'] ? ' en ' . $aviso['barrio_nombre'] : '') . ', Río Gallegos',
            description: implode('. ', $datos) . '. ' . $aviso['descripcion'],
            canonical: $url,
            indexar: $aviso['estado'] === 'activo',
            imagen: $og,
            ogTipo: 'article',
            jsonLd: [JsonLd::aviso($aviso, $fotos, $mapa), JsonLd::migas($migas, $url)],
            imagenAncho: $og ? 1200 : null,
            imagenAlto: $og ? 630 : null,
        );

        return View::render('aviso', [
            'meta' => $meta,
            'aviso' => $aviso,
            'fotos' => $fotos,
            'url' => $url,
            'similares' => $repo->similares($aviso),
            'mapa' => $mapa,
            'migas' => $migas,
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
