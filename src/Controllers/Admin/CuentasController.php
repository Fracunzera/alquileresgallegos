<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Http;
use App\Services\Auth;
use App\Services\Cuentas;
use App\Services\Flash;
use App\Services\Slugger;

final class CuentasController extends Base
{
    public function index(): string
    {
        $filtro = Http::query('ver');
        $where = match ($filtro) {
            'vincular' => "WHERE c.tipo = 'inmobiliaria' AND c.inmobiliaria_id IS NULL AND c.estado = 'activa'",
            'suspendidas' => "WHERE c.estado = 'suspendida'",
            default => '',
        };
        return $this->render('cuentas', 'Cuentas', [
            'filtro' => $filtro,
            'items' => Db::filas(
                "SELECT c.*, i.nombre AS inmobiliaria_nombre,
                        (SELECT COUNT(*) FROM avisos a WHERE a.cuenta_id = c.id) AS total,
                        (SELECT COUNT(*) FROM avisos a WHERE a.cuenta_id = c.id AND a.estado = 'activo') AS activos
                 FROM cuentas c LEFT JOIN inmobiliarias i ON i.id = c.inmobiliaria_id {$where}
                 ORDER BY (c.tipo = 'inmobiliaria' AND c.inmobiliaria_id IS NULL) DESC, c.creado_en DESC"
            ),
            'inmobiliarias' => Db::filas('SELECT id, nombre FROM inmobiliarias ORDER BY nombre'),
        ]);
    }

    public function confiable(string $id): string
    {
        Db::run('UPDATE cuentas SET confiable = 1 - confiable WHERE id = ?', [self::id($id)]);
        $confiable = (bool) Db::valor('SELECT confiable FROM cuentas WHERE id = ?', [self::id($id)]);
        Flash::ok($confiable ? 'Cuenta marcada como confiable: sus avisos se publican sin moderación.' : 'La cuenta vuelve a pasar por moderación.');
        Http::redirigir(AvisosController::volver('/admin/cuentas'));
    }

    public function suspender(string $id): string
    {
        $cuenta = Db::fila('SELECT id, estado FROM cuentas WHERE id = ?', [self::id($id)]);
        if ($cuenta !== null && $cuenta['estado'] === 'activa') {
            $pausados = Cuentas::suspender((int) $cuenta['id'], Auth::adminId());
            Flash::ok("Cuenta suspendida. Avisos pausados: {$pausados}.");
        } elseif ($cuenta !== null) {
            Db::run("UPDATE cuentas SET estado = 'activa' WHERE id = ?", [$cuenta['id']]);
            Flash::ok('Cuenta reactivada. Sus avisos siguen pausados: reactivalos desde Avisos si corresponde.');
        }
        Http::redirigir(AvisosController::volver('/admin/cuentas'));
    }

    /** Vincula a una inmobiliaria existente o crea la ficha con el nombre que pidió. */
    public function vincular(string $id): string
    {
        $cuenta = Db::fila('SELECT * FROM cuentas WHERE id = ?', [self::id($id)]);
        if ($cuenta === null) {
            Http::redirigir('/admin/cuentas');
        }
        $inmobiliariaId = (int) Http::post('inmobiliaria_id');
        if (Http::post('crear') === '1') {
            $nombre = trim((string) ($cuenta['inmobiliaria_solicitada'] ?: $cuenta['nombre']));
            $slug = Slugger::slug($nombre, 120);
            if (Db::valor('SELECT id FROM inmobiliarias WHERE slug = ?', [$slug]) !== null) {
                Flash::error("Ya existe una inmobiliaria con la URL /inmobiliaria/{$slug}. Elegila de la lista.");
                Http::redirigir('/admin/cuentas');
            }
            Db::run(
                'INSERT INTO inmobiliarias (nombre, slug, whatsapp, telefono, email) VALUES (?, ?, ?, ?, ?)',
                [$nombre, $slug, $cuenta['whatsapp'], $cuenta['telefono'], $cuenta['email']]
            );
            $inmobiliariaId = (int) Db::pdo()->lastInsertId();
        }
        if ($inmobiliariaId <= 0 || Db::valor('SELECT id FROM inmobiliarias WHERE id = ?', [$inmobiliariaId]) === null) {
            Flash::error('Elegí una inmobiliaria.');
            Http::redirigir('/admin/cuentas');
        }
        Cuentas::vincularInmobiliaria((int) $cuenta['id'], $inmobiliariaId);
        Flash::ok('Cuenta vinculada. Le avisamos por email que ya puede publicar.');
        Http::redirigir('/admin/cuentas');
    }
}
