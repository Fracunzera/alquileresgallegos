<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\Http;
use App\Services\Estadisticas;

/** Recibe los beacons de la ficha. Siempre responde 204 (no da información a quien lo llame). */
final class EstadisticasController
{
    public function registrar(): string
    {
        http_response_code(204);
        header('Cache-Control: no-store');
        $avisoId = (int) Http::post('aviso');
        $evento = (string) Http::post('evento');
        $origen = $_SERVER['HTTP_ORIGIN'] ?? null;
        $propio = $origen === null || parse_url($origen, PHP_URL_HOST) === parse_url((string) \App\Config::get('APP_URL'), PHP_URL_HOST)
            || parse_url($origen, PHP_URL_HOST) === parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        if (
            $propio
            && $avisoId > 0
            && isset(Estadisticas::EVENTOS[$evento])
            && !Estadisticas::esBot($_SERVER['HTTP_USER_AGENT'] ?? '')
            && Db::valor("SELECT id FROM avisos WHERE id = ? AND estado = 'activo'", [$avisoId]) !== null
        ) {
            Estadisticas::registrar($avisoId, $evento);
        }
        return '';
    }
}
