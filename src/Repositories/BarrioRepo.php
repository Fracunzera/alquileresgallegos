<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;

final class BarrioRepo
{
    /** @return list<array<string, mixed>> */
    public function activos(): array
    {
        return Db::filas('SELECT * FROM barrios WHERE activo = 1 ORDER BY nombre');
    }

    /** @return list<array<string, mixed>> */
    public function todos(): array
    {
        return Db::filas('SELECT * FROM barrios ORDER BY nombre');
    }

    /**
     * Barrios activos con cantidad de avisos activos (solo los que tienen al menos uno).
     *
     * @return list<array<string, mixed>>
     */
    public function conAvisos(): array
    {
        return Db::filas(
            "SELECT b.id, b.nombre, b.slug, COUNT(a.id) AS total, MAX(a.actualizado_en) AS actualizado
             FROM barrios b JOIN avisos a ON a.barrio_id = b.id AND a.estado = 'activo'
             WHERE b.activo = 1 GROUP BY b.id ORDER BY total DESC, b.nombre"
        );
    }

    /** @return array<string, mixed>|null */
    public function porSlug(string $slug): ?array
    {
        return Db::fila('SELECT * FROM barrios WHERE slug = ? AND activo = 1', [$slug]);
    }

    /** @return array<string, mixed>|null */
    public function porId(int $id): ?array
    {
        return Db::fila('SELECT * FROM barrios WHERE id = ?', [$id]);
    }
}
