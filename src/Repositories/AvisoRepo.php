<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db;

final class AvisoRepo
{
    public const POR_PAGINA = 24;

    private const SELECT = <<<'SQL'
        SELECT a.*, b.nombre AS barrio_nombre, b.slug AS barrio_slug,
               i.nombre AS inmobiliaria_nombre, i.slug AS inmobiliaria_slug,
               f.archivo AS foto_archivo, f.ancho AS foto_ancho, f.alto AS foto_alto,
               (SELECT COUNT(*) FROM aviso_fotos fc WHERE fc.aviso_id = a.id) AS cantidad_fotos
        FROM avisos a
        LEFT JOIN barrios b ON b.id = a.barrio_id
        LEFT JOIN inmobiliarias i ON i.id = a.inmobiliaria_id
        LEFT JOIN aviso_fotos f ON f.id = (
            SELECT f2.id FROM aviso_fotos f2 WHERE f2.aviso_id = a.id ORDER BY f2.orden, f2.id LIMIT 1
        )
        SQL;

    /**
     * Listado público (solo activos). Los destacados van siempre primero.
     *
     * @param array{tipo?: string, categoria?: string, barrio_id?: int, inmobiliaria_id?: int,
     *              dorm_min?: int, precio_max?: int, orden?: string, excluir_id?: int} $filtros
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function buscar(array $filtros, int $pagina = 1, int $porPagina = self::POR_PAGINA): array
    {
        [$where, $params] = $this->where($filtros);
        $orden = match ($filtros['orden'] ?? 'recientes') {
            'precio_asc' => 'a.precio IS NULL, a.moneda = \'USD\', a.precio ASC',
            'precio_desc' => 'a.precio IS NULL, a.moneda = \'ARS\', a.precio DESC',
            default => 'a.publicado_en DESC',
        };
        $offset = max(0, ($pagina - 1) * $porPagina);

        $items = Db::filas(
            self::SELECT . " WHERE {$where} ORDER BY a.destacado DESC, {$orden}, a.id DESC LIMIT {$porPagina} OFFSET {$offset}",
            $params
        );
        $total = (int) Db::valor("SELECT COUNT(*) FROM avisos a WHERE {$where}", $params);

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Cantidad de avisos y precio mínimo en ARS (para title y meta description).
     *
     * @param array<string, mixed> $filtros
     * @return array{total: int, desde: ?int}
     */
    public function estadisticas(array $filtros, string $periodo = 'mes'): array
    {
        [$where, $params] = $this->where($filtros);
        $params['periodo_stats'] = $periodo;
        $fila = Db::fila(
            "SELECT COUNT(*) AS total,
                    MIN(CASE WHEN a.moneda = 'ARS' AND a.periodo = :periodo_stats THEN a.precio END) AS desde
             FROM avisos a WHERE {$where}",
            $params
        );
        return ['total' => (int) $fila['total'], 'desde' => $fila['desde'] !== null ? (int) $fila['desde'] : null];
    }

    /** @return array<string, mixed>|null */
    public function porId(int $id): ?array
    {
        return Db::fila(self::SELECT . ' WHERE a.id = ?', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public function fotos(int $avisoId): array
    {
        return Db::filas('SELECT * FROM aviso_fotos WHERE aviso_id = ? ORDER BY orden, id', [$avisoId]);
    }

    /**
     * Avisos parecidos para la ficha: mismo tipo y categoría, primero los del mismo barrio.
     *
     * @param array<string, mixed> $aviso
     * @return list<array<string, mixed>>
     */
    public function similares(array $aviso, int $cantidad = 4): array
    {
        return Db::filas(
            self::SELECT . " WHERE a.estado = 'activo' AND a.id <> :id AND a.tipo = :tipo AND a.categoria = :categoria
              ORDER BY (a.barrio_id <=> :barrio) DESC, a.destacado DESC, a.publicado_en DESC LIMIT {$cantidad}",
            ['id' => $aviso['id'], 'tipo' => $aviso['tipo'], 'categoria' => $aviso['categoria'], 'barrio' => $aviso['barrio_id']]
        );
    }

    /** @return list<array{id: int, slug: string, actualizado_en: string}> */
    public function paraSitemap(): array
    {
        return Db::filas("SELECT id, slug, actualizado_en FROM avisos WHERE estado = 'activo' ORDER BY id");
    }

    /**
     * @param array<string, mixed> $f
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function where(array $f): array
    {
        $condiciones = ["a.estado = 'activo'"];
        $params = [];
        foreach (['tipo', 'categoria', 'barrio_id', 'inmobiliaria_id'] as $campo) {
            if (isset($f[$campo])) {
                $condiciones[] = "a.{$campo} = :{$campo}";
                $params[$campo] = $f[$campo];
            }
        }
        if (isset($f['dorm_min'])) {
            $condiciones[] = 'a.dormitorios >= :dorm_min';
            $params['dorm_min'] = $f['dorm_min'];
        }
        if (isset($f['precio_max'])) {
            $condiciones[] = "a.moneda = 'ARS' AND a.precio <= :precio_max";
            $params['precio_max'] = $f['precio_max'];
        }
        if (isset($f['excluir_id'])) {
            $condiciones[] = 'a.id <> :excluir_id';
            $params['excluir_id'] = $f['excluir_id'];
        }
        return [implode(' AND ', $condiciones), $params];
    }

    /**
     * Listado del panel (todos los estados).
     *
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function listarAdmin(?string $estado, string $q, int $pagina, int $porPagina = 50): array
    {
        $condiciones = ['1 = 1'];
        $params = [];
        if ($estado !== null) {
            $condiciones[] = 'a.estado = :estado';
            $params['estado'] = $estado;
        }
        if ($q !== '') {
            if (ctype_digit($q)) {
                $condiciones[] = 'a.id = :id';
                $params['id'] = (int) $q;
            } else {
                $condiciones[] = '(a.titulo LIKE :q1 OR a.descripcion LIKE :q2 OR a.contacto_whatsapp LIKE :q3)';
                $params['q1'] = $params['q2'] = $params['q3'] = '%' . $q . '%';
            }
        }
        $where = implode(' AND ', $condiciones);
        $offset = max(0, ($pagina - 1) * $porPagina);
        return [
            'items' => Db::filas(self::SELECT . " WHERE {$where} ORDER BY a.actualizado_en DESC LIMIT {$porPagina} OFFSET {$offset}", $params),
            'total' => (int) Db::valor("SELECT COUNT(*) FROM avisos a WHERE {$where}", $params),
        ];
    }

    /** @return array<string, int> cantidad por estado */
    public function contarPorEstado(): array
    {
        $conteo = array_fill_keys(array_keys(\App\Catalogo::ESTADOS), 0);
        foreach (Db::filas('SELECT estado, COUNT(*) AS total FROM avisos GROUP BY estado') as $fila) {
            $conteo[$fila['estado']] = (int) $fila['total'];
        }
        return $conteo;
    }
}
