<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\Repositories\AvisoRepo;
use App\Repositories\BarrioRepo;
use App\Repositories\InmobiliariaRepo;
use App\Seo\JsonLd;
use App\Seo\Meta;
use App\View;

final class HomeController
{
    public function index(): string
    {
        $avisos = new AvisoRepo();
        $recientes = $avisos->buscar([], 1, 12);
        $temporarios = (int) Db::valor("SELECT COUNT(*) FROM avisos WHERE estado = 'activo' AND tipo = 'temporario'");
        $mensuales = $recientes['total'] - $temporarios;

        $description = $recientes['total'] > 0
            ? "{$recientes['total']} alquileres en Río Gallegos en un solo lugar: {$mensuales} mensuales y {$temporarios} temporarios. Casas, departamentos y monoambientes de dueños e inmobiliarias. Contacto por WhatsApp."
            : 'Todos los alquileres de Río Gallegos en un solo lugar: casas, departamentos y monoambientes, mensuales y temporarios. Publicá gratis tu aviso.';

        return View::render('home', [
            'meta' => new Meta(
                title: 'Alquileres en Río Gallegos: casas, departamentos y temporarios',
                description: $description,
                canonical: '/',
                jsonLd: [JsonLd::sitio(), JsonLd::listado('Últimos alquileres publicados en Río Gallegos', $recientes['items'], 0)],
            ),
            'recientes' => $recientes['items'],
            'total' => $recientes['total'],
            'mensuales' => $mensuales,
            'temporarios' => $temporarios,
            'barrios' => (new BarrioRepo())->conAvisos(),
            'todosLosBarrios' => (new BarrioRepo())->activos(),
            'inmobiliarias' => array_filter((new InmobiliariaRepo())->activasConAvisos(), fn ($i) => $i['total'] > 0),
        ]);
    }
}
