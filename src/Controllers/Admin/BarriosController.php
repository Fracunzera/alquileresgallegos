<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Http;
use App\NoEncontrado;
use App\Seo\Landings;
use App\Services\Flash;
use App\Services\Slugger;

final class BarriosController extends Base
{
    public function index(): string
    {
        return $this->render('barrios', 'Barrios', [
            'items' => Db::filas(
                "SELECT b.*, COUNT(a.id) AS total, SUM(a.estado = 'activo') AS activos
                 FROM barrios b LEFT JOIN avisos a ON a.barrio_id = b.id
                 GROUP BY b.id ORDER BY b.nombre"
            ),
            'sugeridos' => Db::filas(
                "SELECT barrio_sugerido, COUNT(*) AS total FROM avisos
                 WHERE barrio_id IS NULL AND barrio_sugerido IS NOT NULL AND estado = 'pendiente'
                 GROUP BY barrio_sugerido ORDER BY total DESC"
            ),
        ]);
    }

    public function nuevo(): string
    {
        return $this->form(null, ['nombre' => Http::query('nombre') ?? '', 'activo' => 1], []);
    }

    public function crear(): string
    {
        [$datos, $errores] = $this->validar(null);
        if ($errores) {
            return $this->form(null, $_POST, $errores);
        }
        Db::run(
            'INSERT INTO barrios (nombre, slug, intro, meta_description, activo) VALUES (?, ?, ?, ?, ?)',
            [$datos['nombre'], $datos['slug'], $datos['intro'], $datos['meta_description'], $datos['activo']]
        );
        Flash::ok("Barrio «{$datos['nombre']}» creado.");
        Http::redirigir('/admin/barrios');
    }

    public function editar(string $id): string
    {
        $barrio = Db::fila('SELECT * FROM barrios WHERE id = ?', [self::id($id)]) ?? throw new NoEncontrado();
        return $this->form($barrio, $barrio, []);
    }

    public function guardar(string $id): string
    {
        $barrio = Db::fila('SELECT * FROM barrios WHERE id = ?', [self::id($id)]) ?? throw new NoEncontrado();
        [$datos, $errores] = $this->validar((int) $barrio['id']);
        if ($errores) {
            return $this->form($barrio, $_POST, $errores);
        }
        Db::run(
            'UPDATE barrios SET nombre = ?, slug = ?, intro = ?, meta_description = ?, activo = ? WHERE id = ?',
            [$datos['nombre'], $datos['slug'], $datos['intro'], $datos['meta_description'], $datos['activo'], $barrio['id']]
        );
        if ($datos['slug'] !== $barrio['slug']) {
            Flash::error("Cambió la URL del barrio: la anterior (/alquileres-{$barrio['slug']}-rio-gallegos) dejará de funcionar.");
        }
        Flash::ok('Cambios guardados.');
        Http::redirigir('/admin/barrios');
    }

    public function borrar(string $id): string
    {
        $total = (int) Db::valor('SELECT COUNT(*) FROM avisos WHERE barrio_id = ?', [self::id($id)]);
        if ($total > 0) {
            Flash::error("No se puede borrar: tiene {$total} avisos. Desactivalo o reasigná los avisos.");
        } else {
            Db::run('DELETE FROM barrios WHERE id = ?', [self::id($id)]);
            Flash::ok('Barrio borrado.');
        }
        Http::redirigir('/admin/barrios');
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function validar(?int $id): array
    {
        $err = [];
        $nombre = mb_substr(trim((string) Http::post('nombre')), 0, 80);
        $slug = Slugger::slug(Http::post('slug') ?: $nombre, 80);
        if (mb_strlen($nombre) < 2) {
            $err['nombre'] = 'Falta el nombre.';
        }
        if (in_array($slug, Landings::SLUGS_RESERVADOS, true)) {
            $err['slug'] = 'Esa URL está reservada para otra página del sitio.';
        } elseif (Db::valor('SELECT id FROM barrios WHERE slug = ? AND id <> ?', [$slug, $id ?? 0]) !== null) {
            $err['slug'] = 'Ya existe otro barrio con esa URL.';
        }
        $meta = trim((string) Http::post('meta_description'));
        return [[
            'nombre' => $nombre,
            'slug' => $slug,
            'intro' => trim((string) Http::post('intro')) ?: null,
            'meta_description' => $meta !== '' ? mb_substr($meta, 0, 160) : null,
            'activo' => Http::post('activo') ? 1 : 0,
        ], $err];
    }

    /** @param array<string, mixed>|null $barrio @param array<string, mixed> $v @param array<string, string> $err */
    private function form(?array $barrio, array $v, array $err): string
    {
        return $this->render('barrio-form', $barrio ? $barrio['nombre'] : 'Nuevo barrio', ['barrio' => $barrio, 'v' => $v, 'err' => $err]);
    }
}
