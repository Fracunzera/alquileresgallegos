<?php

declare(strict_types=1);

/*
 * Funciones cortas para las plantillas.
 */

use App\Config;

/** Escapa para HTML. Todo lo que venga de la base pasa por acá. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL absoluta del sitio (canonical, sitemap, Open Graph). */
function url_abs(string $path = '/'): string
{
    return rtrim((string) Config::get('APP_URL', ''), '/') . $path;
}

/** URL relativa con querystring (descarta valores vacíos). @param array<string, mixed> $query */
function url_con(string $path, array $query = []): string
{
    $query = array_filter($query, fn ($v) => $v !== null && $v !== '' && $v !== false);
    return $path . ($query ? '?' . http_build_query($query) : '');
}

/** Asset con versión por fecha de modificación para poder cachearlo largo. */
function asset(string $path): string
{
    $archivo = RAIZ . '/public' . $path;
    return $path . (is_file($archivo) ? '?v=' . filemtime($archivo) : '');
}

function app_nombre(): string
{
    return (string) Config::get('APP_NOMBRE', 'Alquileres Río Gallegos');
}
