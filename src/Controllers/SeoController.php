<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Catalogo;
use App\Db;
use App\Repositories\AvisoRepo;
use App\Repositories\BarrioRepo;
use App\Seo\Landings;

final class SeoController
{
    /**
     * Sitemap dinámico: home, landings con avisos, barrios con avisos, inmobiliarias con avisos
     * y cada aviso activo con sus fotos (extensión de imágenes para Google Imágenes).
     */
    public function sitemap(): string
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');

        $avisos = new AvisoRepo();
        $urls = [];
        $ultimo = (string) Db::valor("SELECT MAX(actualizado_en) FROM avisos WHERE estado = 'activo'");
        $urls[] = ['loc' => '/', 'lastmod' => $ultimo];

        foreach (Landings::fijas() as $path => $landing) {
            if ($avisos->estadisticas($landing['filtro'])['total'] > 0) {
                $urls[] = ['loc' => $path, 'lastmod' => $ultimo];
            }
        }
        foreach ((new BarrioRepo())->conAvisos() as $barrio) {
            $urls[] = ['loc' => Catalogo::urlBarrio($barrio['slug']), 'lastmod' => $barrio['actualizado']];
        }
        foreach (Db::filas(
            "SELECT i.slug, MAX(a.actualizado_en) AS actualizado FROM inmobiliarias i
             JOIN avisos a ON a.inmobiliaria_id = i.id AND a.estado = 'activo'
             WHERE i.activo = 1 GROUP BY i.id"
        ) as $inmo) {
            $urls[] = ['loc' => Catalogo::urlInmobiliaria($inmo['slug']), 'lastmod' => $inmo['actualizado']];
        }

        $fotos = [];
        foreach (Db::filas(
            "SELECT f.aviso_id, f.archivo FROM aviso_fotos f JOIN avisos a ON a.id = f.aviso_id
             WHERE a.estado = 'activo' ORDER BY f.aviso_id, f.orden, f.id"
        ) as $foto) {
            $fotos[$foto['aviso_id']][] = $foto['archivo'];
        }
        foreach ($avisos->paraSitemap() as $aviso) {
            $urls[] = [
                'loc' => Catalogo::urlAviso((int) $aviso['id'], $aviso['slug']),
                'lastmod' => $aviso['actualizado_en'],
                'imagenes' => $fotos[$aviso['id']] ?? [],
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>' . e(url_abs($url['loc'])) . '</loc>';
            if (!empty($url['lastmod'])) {
                $xml .= '<lastmod>' . (new \DateTimeImmutable($url['lastmod']))->format(DATE_ATOM) . '</lastmod>';
            }
            foreach ($url['imagenes'] ?? [] as $archivo) {
                $xml .= '<image:image><image:loc>' . e(url_abs(Catalogo::urlFoto($archivo, 1200))) . '</image:loc></image:image>';
            }
            $xml .= "</url>\n";
        }
        return $xml . "</urlset>\n";
    }

    public function robots(): string
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=86400');
        return implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cuenta',
            'Disallow: /verificar/',
            'Disallow: /confirmar/',
            'Disallow: /aviso/*/denunciar',
            'Disallow: /publicar/gracias',
            'Disallow: /buscar',
            'Disallow: /estadisticas',
            '',
            'Sitemap: ' . url_abs('/sitemap.xml'),
            '',
        ]);
    }
}
