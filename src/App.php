<?php

declare(strict_types=1);

namespace App;

use App\Seo\Meta;
use App\Services\Auth;

final class App
{
    public static function router(): Router
    {
        $router = new Router();
        (require RAIZ . '/src/rutas.php')($router);
        return $router;
    }

    public static function correr(): void
    {
        $path = Http::path();
        if ($path !== '/' && str_ends_with($path, '/')) {
            $query = $_SERVER['QUERY_STRING'] ?? '';
            Http::redirigir(rtrim($path, '/') . ($query !== '' ? '?' . $query : ''), 301);
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        try {
            $cuerpo = self::router()->despachar(Http::metodo(), $path);
            self::cabecerasPorDefecto($path);
            echo $cuerpo;
        } catch (NoEncontrado) {
            self::error(404, 'No encontramos esta página', 'Puede que el aviso ya no esté publicado o que la dirección esté mal escrita.');
        } catch (MetodoNoPermitido) {
            self::error(405, 'Método no permitido', '');
        } catch (\Throwable $e) {
            error_log((string) $e);
            if (Config::bool('APP_DEBUG')) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
                echo $e;
                return;
            }
            self::error(500, 'Algo salió mal', 'Probá de nuevo en unos minutos.');
        }
    }

    private static function cabecerasPorDefecto(string $path): void
    {
        $tiene = static function (string $nombre): bool {
            foreach (headers_list() as $h) {
                if (stripos($h, $nombre . ':') === 0) {
                    return true;
                }
            }
            return false;
        };
        if (!$tiene('Content-Type')) {
            header('Content-Type: text/html; charset=utf-8');
        }
        if (!$tiene('Cache-Control')) {
            $privado = str_starts_with($path, '/admin') || isset($_COOKIE[Auth::COOKIE]) || Http::metodo() !== 'GET';
            header('Cache-Control: ' . ($privado ? 'private, no-store' : 'public, max-age=300'));
        }
    }

    private static function error(int $codigo, string $titulo, string $texto): void
    {
        http_response_code($codigo);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        try {
            echo View::render('error', [
                'meta' => new Meta(title: $titulo, description: $texto, indexar: false),
                'codigo' => $codigo,
                'titulo' => $titulo,
                'texto' => $texto,
            ]);
        } catch (\Throwable $e) {
            // Si falla hasta la plantilla (ej. base caída), respuesta mínima.
            error_log((string) $e);
            echo '<!doctype html><meta charset="utf-8"><title>' . e($titulo) . '</title><h1>' . e($titulo) . '</h1><p>' . e($texto) . '</p>';
        }
    }
}
