<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Http;
use App\NoEncontrado;
use App\Services\Flash;
use App\Services\Fotos;
use App\Services\Slugger;
use App\Services\Telefono;

final class InmobiliariasController extends Base
{
    public function index(): string
    {
        return $this->render('inmobiliarias', 'Inmobiliarias', [
            'items' => Db::filas(
                "SELECT i.*, COUNT(a.id) AS total, SUM(a.estado = 'activo') AS activos
                 FROM inmobiliarias i LEFT JOIN avisos a ON a.inmobiliaria_id = i.id
                 GROUP BY i.id ORDER BY i.nombre"
            ),
        ]);
    }

    public function nueva(): string
    {
        return $this->form(null, ['activo' => 1], []);
    }

    public function crear(): string
    {
        [$datos, $errores] = $this->validar(null);
        if ($errores) {
            return $this->form(null, $_POST, $errores);
        }
        Db::run(
            'INSERT INTO inmobiliarias (' . implode(', ', array_keys($datos)) . ') VALUES (' . implode(', ', array_fill(0, count($datos), '?')) . ')',
            array_values($datos)
        );
        $id = (int) Db::pdo()->lastInsertId();
        $this->subirLogo($id);
        Flash::ok("Inmobiliaria «{$datos['nombre']}» creada.");
        Http::redirigir('/admin/inmobiliarias');
    }

    public function editar(string $id): string
    {
        $inmo = Db::fila('SELECT * FROM inmobiliarias WHERE id = ?', [self::id($id)]) ?? throw new NoEncontrado();
        return $this->form($inmo, $inmo, []);
    }

    public function guardar(string $id): string
    {
        $inmo = Db::fila('SELECT * FROM inmobiliarias WHERE id = ?', [self::id($id)]) ?? throw new NoEncontrado();
        [$datos, $errores] = $this->validar((int) $inmo['id']);
        if ($errores) {
            return $this->form($inmo, $_POST + ['logo' => $inmo['logo']], $errores);
        }
        $sets = implode(', ', array_map(fn ($c) => "{$c} = ?", array_keys($datos)));
        Db::run("UPDATE inmobiliarias SET {$sets} WHERE id = ?", [...array_values($datos), $inmo['id']]);
        $this->subirLogo((int) $inmo['id']);
        Flash::ok('Cambios guardados.');
        Http::redirigir('/admin/inmobiliarias');
    }

    public function borrar(string $id): string
    {
        $total = (int) Db::valor('SELECT COUNT(*) FROM avisos WHERE inmobiliaria_id = ?', [self::id($id)]);
        if ($total > 0) {
            Flash::error("No se puede borrar: tiene {$total} avisos. Desactivala en su lugar.");
        } else {
            Db::run('DELETE FROM inmobiliarias WHERE id = ?', [self::id($id)]);
            Flash::ok('Inmobiliaria borrada.');
        }
        Http::redirigir('/admin/inmobiliarias');
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function validar(?int $id): array
    {
        $err = [];
        $nombre = trim((string) Http::post('nombre'));
        $slug = Slugger::slug(Http::post('slug') ?: $nombre, 120);
        if (mb_strlen($nombre) < 2) {
            $err['nombre'] = 'Falta el nombre.';
        }
        if (Db::valor('SELECT id FROM inmobiliarias WHERE slug = ? AND id <> ?', [$slug, $id ?? 0]) !== null) {
            $err['slug'] = 'Ya existe otra inmobiliaria con esa URL.';
        }
        $wa = Http::post('whatsapp') ?: null;
        $waNormalizado = $wa !== null ? Telefono::whatsapp($wa) : null;
        if ($wa !== null && $waNormalizado === null) {
            $err['whatsapp'] = 'Revisá el número de WhatsApp.';
        }
        $web = Http::post('web') ?: null;
        if ($web !== null && !preg_match('#^https?://#i', $web)) {
            $web = 'https://' . $web;
        }
        $email = Http::post('email') ?: null;
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $err['email'] = 'Revisá el email.';
        }
        return [[
            'nombre' => mb_substr($nombre, 0, 120),
            'slug' => $slug,
            'descripcion' => Http::post('descripcion') ?: null,
            'telefono' => mb_substr((string) Http::post('telefono'), 0, 30) ?: null,
            'whatsapp' => $waNormalizado,
            'email' => $email,
            'web' => $web !== null ? mb_substr($web, 0, 255) : null,
            'direccion' => mb_substr((string) Http::post('direccion'), 0, 160) ?: null,
            'activo' => Http::post('activo') ? 1 : 0,
        ], $err];
    }

    private function subirLogo(int $id): void
    {
        $archivo = $_FILES['logo'] ?? null;
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            return;
        }
        $base = 'inmobiliarias/' . $id . '-' . bin2hex(random_bytes(4));
        try {
            Fotos::procesar($archivo['tmp_name'], Fotos::directorioBase() . '/' . $base);
            Db::run('UPDATE inmobiliarias SET logo = ? WHERE id = ?', ['/uploads/' . $base . '-400.webp', $id]);
        } catch (\RuntimeException $e) {
            Flash::error('Logo: ' . $e->getMessage());
        }
    }

    /** @param array<string, mixed>|null $inmo @param array<string, mixed> $v @param array<string, string> $err */
    private function form(?array $inmo, array $v, array $err): string
    {
        return $this->render('inmobiliaria-form', $inmo ? $inmo['nombre'] : 'Nueva inmobiliaria', ['inmo' => $inmo, 'v' => $v, 'err' => $err]);
    }
}
