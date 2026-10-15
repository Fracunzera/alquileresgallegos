<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http;
use App\Seo\Meta;
use App\Services\CuentaAuth;
use App\Services\Cuentas;
use App\View;

/**
 * /publicar: para publicar hace falta una cuenta (gratis, con email confirmado).
 * Esta página explica cómo publicar y lleva al registro; con sesión iniciada va directo al formulario.
 * Se mantiene indexable: "publicar alquiler gratis en Río Gallegos" es una búsqueda que conviene captar.
 */
final class PublicarController
{
    public function form(): string
    {
        // Cambia según haya sesión o no: el navegador no la puede guardar en caché.
        header('Cache-Control: private, no-cache');
        if (CuentaAuth::cuenta() !== null) {
            Http::redirigir('/cuenta/avisos/nuevo');
        }
        return View::render('publicar', [
            'meta' => new Meta(
                title: 'Publicar alquiler gratis en Río Gallegos',
                description: 'Publicá gratis tu casa, departamento o monoambiente en alquiler en Río Gallegos. Creá tu cuenta en un minuto, cargá fotos y recibí consultas directo por WhatsApp.',
                canonical: '/publicar',
            ),
            'maxParticular' => Cuentas::maxParticular(),
        ]);
    }

    /** El formulario anónimo ya no existe: cualquier envío viejo o de un bot va al registro. */
    public function enviar(): string
    {
        Http::redirigir('/cuenta/registro', 303);
    }
}
