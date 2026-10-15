<?php

declare(strict_types=1);

namespace App;

/**
 * Router mínimo. Las rutas se evalúan en el orden en que se registran, así las fijas
 * (/alquileres-mensuales-rio-gallegos) ganan sobre las de patrón (/alquileres-{barrio}-rio-gallegos).
 */
final class Router
{
    /** @var list<array{metodos: list<string>, regex: string, handler: callable|array{class-string, string}}> */
    private array $rutas = [];

    /** @param callable|array{class-string, string} $handler */
    public function get(string $patron, callable|array $handler): void
    {
        $this->agregar(['GET', 'HEAD'], $patron, $handler);
    }

    /** @param callable|array{class-string, string} $handler */
    public function post(string $patron, callable|array $handler): void
    {
        $this->agregar(['POST'], $patron, $handler);
    }

    /** @param list<string> $metodos @param callable|array{class-string, string} $handler */
    public function agregar(array $metodos, string $patron, callable|array $handler): void
    {
        // {nombre} → cualquier cosa sin barra; {nombre:regex} → regex propia.
        $regex = preg_replace_callback(
            '/\{(\w+)(?::([^}]+))?\}/',
            fn (array $m) => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $patron
        );
        $this->rutas[] = ['metodos' => $metodos, 'regex' => '#^' . $regex . '$#u', 'handler' => $handler];
    }

    /**
     * @return array{0: callable|array{class-string, string}, 1: array<string, string>}|null
     *         null si no hay ruta; lanza MetodoNoPermitido si la ruta existe con otro método.
     */
    public function resolver(string $metodo, string $path): ?array
    {
        $otroMetodo = false;
        foreach ($this->rutas as $ruta) {
            if (!preg_match($ruta['regex'], $path, $m)) {
                continue;
            }
            if (!in_array($metodo, $ruta['metodos'], true)) {
                $otroMetodo = true;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return [$ruta['handler'], array_map('rawurldecode', $params)];
        }
        if ($otroMetodo) {
            throw new MetodoNoPermitido();
        }
        return null;
    }

    public function despachar(string $metodo, string $path): string
    {
        $resultado = $this->resolver($metodo, $path);
        if ($resultado === null) {
            throw new NoEncontrado();
        }
        [$handler, $params] = $resultado;
        if (is_array($handler)) {
            $handler = [new $handler[0](), $handler[1]];
        }
        return (string) $handler(...$params);
    }
}
