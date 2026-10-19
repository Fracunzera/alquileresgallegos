<?php

declare(strict_types=1);

namespace App\Controllers;

use App\NoEncontrado;
use App\Seo\Guias;
use App\Seo\JsonLd;
use App\Seo\Meta;
use App\View;

final class GuiaController
{
    public function indice(): string
    {
        $migas = [['Inicio', '/'], ['Guías', null]];
        return View::render('guias', [
            'meta' => new Meta(
                title: 'Guías para alquilar en Río Gallegos',
                description: 'Guías para inquilinos y dueños en Río Gallegos: qué dice hoy la ley de alquileres, qué requisitos piden, cómo evitar estafas y qué revisar en la visita.',
                canonical: '/guias',
                jsonLd: [JsonLd::migas($migas, '/guias')],
            ),
            'guias' => Guias::todas(),
            'migas' => $migas,
        ]);
    }

    public function ver(string $slug): string
    {
        $guia = Guias::porSlug($slug) ?? throw new NoEncontrado();
        $path = Guias::url($slug);
        $migas = [['Inicio', '/'], ['Guías', '/guias'], [$guia['h1'], null]];

        return View::render('guia', [
            'meta' => new Meta(
                title: $guia['title'],
                description: $guia['description'],
                canonical: $path,
                ogTipo: 'article',
                jsonLd: [
                    JsonLd::articulo($guia['h1'], $guia['description'], $path, Guias::REVISADAS_EL),
                    JsonLd::migas($migas, $path),
                ],
            ),
            'guia' => $guia,
            'slug' => $slug,
            'otras' => array_diff_key(Guias::todas(), [$slug => true]),
            'migas' => $migas,
        ]);
    }
}
