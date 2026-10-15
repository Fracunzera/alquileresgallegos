<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;

final class InmobiliariaRepo
{
    /** @return list<array<string, mixed>> */
    public function todas(): array
    {
        return Db::filas('SELECT * FROM inmobiliarias ORDER BY nombre');
    }

    /**
     * Inmobiliarias activas con cantidad de avisos activos.
     *
     * @return list<array<string, mixed>>
     */
    public function activasConAvisos(): array
    {
        return Db::filas(
            "SELECT i.*, COUNT(a.id) AS total
             FROM inmobiliarias i LEFT JOIN avisos a ON a.inmobiliaria_id = i.id AND a.estado = 'activo'
             WHERE i.activo = 1 GROUP BY i.id ORDER BY total DESC, i.nombre"
        );
    }

    /** @return array<string, mixed>|null */
    public function porSlug(string $slug): ?array
    {
        return Db::fila('SELECT * FROM inmobiliarias WHERE slug = ? AND activo = 1', [$slug]);
    }

    /** @return array<string, mixed>|null */
    public function porId(int $id): ?array
    {
        return Db::fila('SELECT * FROM inmobiliarias WHERE id = ?', [$id]);
    }
}
