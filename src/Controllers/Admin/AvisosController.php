<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Catalogo;
use App\Config;
use App\Db;
use App\Frescura\ColaVerificacion;
use App\Http;
use App\NoEncontrado;
use App\Repositories\AvisoRepo;
use App\Repositories\BarrioRepo;
use App\Repositories\InmobiliariaRepo;
use App\Services\AvisoService;
use App\Services\Auth;
use App\Services\Extractor;
use App\Services\Flash;
use App\Services\Fotos;

final class AvisosController extends Base
{
    /** Campos que se copian al usar "Guardar y cargar otro" (mismo anunciante, misma tanda). */
    private const CAMPOS_COPIA = ['tipo', 'categoria', 'barrio_id', 'periodo', 'moneda', 'contacto_whatsapp', 'contacto_telefono',
        'contacto_email', 'publicador', 'inmobiliaria_id'];

    public function inicio(): string
    {
        $repo = new AvisoRepo();
        return $this->render('inicio', 'Panel', [
            'conteo' => $repo->contarPorEstado(),
            'enCola' => ColaVerificacion::contar(),
            'recientes' => $repo->listarAdmin(null, '', 1, 8)['items'],
        ]);
    }

    public function index(): string
    {
        $estado = Http::query('estado');
        $estado = isset(Catalogo::ESTADOS[$estado ?? '']) ? $estado : null;
        $q = Http::query('q') ?? '';
        $pagina = max(1, (int) (Http::query('pagina') ?? 1));
        $resultado = (new AvisoRepo())->listarAdmin($estado, $q, $pagina);

        return $this->render('avisos', 'Avisos', [
            'items' => $resultado['items'],
            'total' => $resultado['total'],
            'estado' => $estado,
            'q' => $q,
            'pagina' => $pagina,
            'ultima' => max(1, (int) ceil($resultado['total'] / 50)),
            'conteo' => (new AvisoRepo())->contarPorEstado(),
        ]);
    }

    public function nuevo(): string
    {
        $valores = ['tipo' => 'mensual', 'periodo' => 'mes', 'moneda' => 'ARS', 'publicador' => 'particular', 'estado' => 'activo'];
        $copiar = Http::query('copiar');
        if ($copiar !== null && ctype_digit($copiar) && ($origen = Db::fila('SELECT * FROM avisos WHERE id = ?', [(int) $copiar]))) {
            $valores = array_intersect_key($origen, array_flip(self::CAMPOS_COPIA)) + $valores;
        }
        $inmo = Http::query('inmobiliaria');
        if ($inmo !== null && ctype_digit($inmo)) {
            $valores['publicador'] = 'inmobiliaria';
            $valores['inmobiliaria_id'] = (int) $inmo;
        }
        return $this->formulario(null, $valores, []);
    }

    public function crear(): string
    {
        if (Http::post('accion') === 'autocompletar') {
            return $this->autocompletar(null);
        }
        [$datos, $errores] = AvisoService::validar($_POST, false);
        if ($errores) {
            return $this->formulario(null, $_POST, $errores);
        }
        $estado = in_array(Http::post('estado'), ['activo', 'pausado'], true) ? Http::post('estado') : 'activo';
        $id = AvisoService::crear($datos, 'admin', $estado);
        $erroresFotos = Fotos::agregarSubidas($id, $_FILES['fotos'] ?? [], Config::int('FOTOS_MAX_POR_AVISO', 12));

        Flash::ok("Aviso n.º {$id} guardado.");
        foreach ($erroresFotos as $error) {
            Flash::error($error);
        }
        Http::redirigir(Http::post('accion') === 'guardar_y_otro' ? "/admin/avisos/nuevo?copiar={$id}" : "/admin/avisos/{$id}");
    }

    public function editar(string $id): string
    {
        $aviso = Db::fila('SELECT * FROM avisos WHERE id = ?', [self::id($id)]) ?? throw new NoEncontrado();
        return $this->formulario($aviso, $aviso, []);
    }

    public function guardar(string $id): string
    {
        $aviso = Db::fila('SELECT * FROM avisos WHERE id = ?', [self::id($id)]) ?? throw new NoEncontrado();
        if (Http::post('accion') === 'autocompletar') {
            return $this->autocompletar($aviso);
        }
        [$datos, $errores] = AvisoService::validar($_POST, false);
        if ($errores) {
            return $this->formulario($aviso, $_POST, $errores);
        }
        AvisoService::actualizar((int) $aviso['id'], $datos);
        $erroresFotos = Fotos::agregarSubidas((int) $aviso['id'], $_FILES['fotos'] ?? [], Config::int('FOTOS_MAX_POR_AVISO', 12));

        Flash::ok('Cambios guardados.');
        foreach ($erroresFotos as $error) {
            Flash::error($error);
        }
        Http::redirigir(Http::post('accion') === 'guardar_y_otro' ? "/admin/avisos/nuevo?copiar={$aviso['id']}" : "/admin/avisos/{$aviso['id']}");
    }

    public function estado(string $id): string
    {
        try {
            AvisoService::cambiarEstado(self::id($id), (string) Http::post('estado'), Http::post('motivo') ?: null, Auth::adminId());
            Flash::ok('Estado actualizado: ' . Catalogo::ESTADOS[Http::post('estado')] . '.');
        } catch (\RuntimeException | \InvalidArgumentException $e) {
            Flash::error($e->getMessage());
        }
        Http::redirigir(self::volver("/admin/avisos/{$id}"));
    }

    public function destacar(string $id): string
    {
        Db::run('UPDATE avisos SET destacado = 1 - destacado WHERE id = ?', [self::id($id)]);
        Flash::ok('Destacado actualizado.');
        Http::redirigir(self::volver("/admin/avisos/{$id}"));
    }

    public function borrar(string $id): string
    {
        AvisoService::borrar(self::id($id));
        Flash::ok("Aviso n.º {$id} borrado.");
        Http::redirigir('/admin/avisos');
    }

    /** Reordenar, editar el texto alternativo y borrar fotos. */
    public function fotos(string $id): string
    {
        $avisoId = self::id($id);
        $borrar = array_map('intval', (array) ($_POST['borrar'] ?? []));
        foreach ((array) ($_POST['orden'] ?? []) as $fotoId => $orden) {
            if (in_array((int) $fotoId, $borrar, true)) {
                continue;
            }
            $alt = trim((string) ($_POST['alt'][$fotoId] ?? ''));
            Db::run(
                'UPDATE aviso_fotos SET orden = ?, alt = ? WHERE id = ? AND aviso_id = ?',
                [max(0, (int) $orden), $alt !== '' ? mb_substr($alt, 0, 200) : null, (int) $fotoId, $avisoId]
            );
        }
        foreach ($borrar as $fotoId) {
            if (Db::valor('SELECT id FROM aviso_fotos WHERE id = ? AND aviso_id = ?', [$fotoId, $avisoId]) !== null) {
                Fotos::borrar($fotoId);
            }
        }
        Flash::ok('Fotos actualizadas.');
        Http::redirigir("/admin/avisos/{$avisoId}#fotos");
    }

    /**
     * Completa los campos vacíos con lo que se detecta en el texto pegado, sin guardar.
     *
     * @param array<string, mixed>|null $aviso
     */
    private function autocompletar(?array $aviso): string
    {
        $valores = $_POST;
        $detectado = Extractor::extraer((string) ($valores['descripcion'] ?? ''), (new BarrioRepo())->activos());
        $completados = [];
        foreach ($detectado as $campo => $valor) {
            if (trim((string) ($valores[$campo] ?? '')) === '' || in_array($campo, ['tipo', 'periodo', 'categoria', 'moneda'], true)) {
                $valores[$campo] = $valor;
                $completados[] = $campo;
            }
        }
        if (trim((string) ($valores['titulo'] ?? '')) === '' && isset($valores['categoria'])) {
            $valores['titulo'] = self::tituloSugerido($valores);
            $completados[] = 'titulo';
        }
        $mensaje = $completados
            ? 'Detectado en el texto: ' . implode(', ', $completados) . '. Revisá antes de guardar.'
            : 'No se detectaron datos en el texto.';
        return $this->formulario($aviso, $valores, [], $mensaje);
    }

    /** @param array<string, mixed> $v */
    private static function tituloSugerido(array $v): string
    {
        $titulo = Catalogo::CATEGORIAS[$v['categoria']] ?? 'Propiedad';
        if (!empty($v['dormitorios']) && !in_array($v['categoria'], ['monoambiente', 'local', 'habitacion'], true)) {
            $titulo .= ' de ' . $v['dormitorios'] . ((int) $v['dormitorios'] === 1 ? ' dormitorio' : ' dormitorios');
        }
        if (!empty($v['barrio_id']) && ($barrio = (new BarrioRepo())->porId((int) $v['barrio_id']))) {
            $titulo .= ' en ' . $barrio['nombre'];
        }
        return $titulo . (($v['tipo'] ?? '') === 'temporario' ? ' por día' : '');
    }

    /**
     * @param array<string, mixed>|null $aviso registro existente (null = alta)
     * @param array<string, mixed> $valores
     * @param array<string, string> $errores
     */
    private function formulario(?array $aviso, array $valores, array $errores, ?string $mensaje = null): string
    {
        $fotos = $aviso !== null ? (new AvisoRepo())->fotos((int) $aviso['id']) : [];
        return $this->render('aviso-form', $aviso ? "Aviso n.º {$aviso['id']}" : 'Nuevo aviso', [
            'aviso' => $aviso,
            'v' => $valores,
            'err' => $errores,
            'mensaje' => $mensaje,
            'fotos' => $fotos,
            'barrios' => (new BarrioRepo())->todos(),
            'inmobiliarias' => (new InmobiliariaRepo())->todas(),
            'historial' => $aviso ? Db::filas('SELECT v.*, ad.nombre AS admin_nombre FROM verificaciones v LEFT JOIN admins ad ON ad.id = v.admin_id WHERE aviso_id = ? ORDER BY v.creado_en DESC LIMIT 20', [$aviso['id']]) : [],
        ]);
    }

    /** Vuelve a la página indicada en el formulario, solo si es del panel. */
    public static function volver(string $porDefecto): string
    {
        $volver = Http::post('volver') ?? '';
        return str_starts_with($volver, '/admin') && !str_starts_with($volver, '//') ? $volver : $porDefecto;
    }
}
