<?php

declare(strict_types=1);

namespace App\Controllers\Cuenta;

use App\Catalogo;
use App\Config;
use App\Db;
use App\Frescura\ColaVerificacion;
use App\Http;
use App\Mail\Mail;
use App\NoEncontrado;
use App\Repositories\AvisoRepo;
use App\Repositories\BarrioRepo;
use App\Seo\Meta;
use App\Services\AvisoService;
use App\Services\Csrf;
use App\Services\CuentaAuth;
use App\Services\Cuentas;
use App\Services\Estadisticas;
use App\Services\Flash;
use App\Services\Fotos;
use App\Services\Telefono;
use App\View;

/**
 * Panel "Mi cuenta" del anunciante. Cada acción verifica que el aviso sea de la cuenta.
 */
final class PanelController
{
    /** @var array<string, mixed> */
    private array $cuenta;

    public function __construct()
    {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: private, no-store');
        $this->cuenta = CuentaAuth::requerir();
        if (Http::metodo() === 'POST') {
            Csrf::verificar();
        }
    }

    public function inicio(): string
    {
        $avisos = Db::filas(
            "SELECT a.*, b.nombre AS barrio_nombre,
                    (SELECT f.archivo FROM aviso_fotos f WHERE f.aviso_id = a.id ORDER BY f.orden, f.id LIMIT 1) AS foto_archivo
             FROM avisos a LEFT JOIN barrios b ON b.id = a.barrio_id
             WHERE a.cuenta_id = ? ORDER BY FIELD(a.estado, 'activo', 'pendiente', 'pausado', 'alquilado', 'rechazado'), a.actualizado_en DESC",
            [$this->cuenta['id']]
        );
        $stats = Estadisticas::resumen(array_map(fn ($a) => (int) $a['id'], $avisos));
        [$puede, $motivo] = Cuentas::puedePublicar($this->cuenta);

        return $this->render('cuenta/panel', 'Mi cuenta', [
            'cuenta' => $this->cuenta,
            'avisos' => $avisos,
            'stats' => $stats,
            'puede' => $puede,
            'motivo' => $motivo,
            'diasVerificacion' => ColaVerificacion::diasVerificacion(),
            'diasGracia' => ColaVerificacion::diasGracia(),
            'pideClaveActual' => $this->cuenta['password_hash'] !== null && !CuentaAuth::entroPorLink(),
            'flash' => Flash::sacar(),
        ]);
    }

    public function datos(): string
    {
        $nombre = mb_substr(trim((string) Http::post('nombre')), 0, 120);
        $wa = trim((string) Http::post('whatsapp'));
        $waNormalizado = $wa !== '' ? Telefono::whatsapp($wa) : null;
        if (mb_strlen($nombre) < 2 || ($wa !== '' && $waNormalizado === null)) {
            Flash::error('Revisá el nombre y el WhatsApp.');
        } else {
            Db::run(
                'UPDATE cuentas SET nombre = ?, whatsapp = ?, telefono = ? WHERE id = ?',
                [$nombre, $waNormalizado, mb_substr(trim((string) Http::post('telefono')), 0, 30) ?: null, $this->cuenta['id']]
            );
            Flash::ok('Datos guardados.');
        }
        Http::redirigir('/cuenta#datos', 303);
    }

    /** Elegir o cambiar la contraseña. Pide la actual salvo que haya entrado con un link del email. */
    public function clave(): string
    {
        $nueva = (string) ($_POST['clave'] ?? '');
        if ($this->cuenta['password_hash'] !== null && !CuentaAuth::entroPorLink()
            && !password_verify((string) ($_POST['clave_actual'] ?? ''), $this->cuenta['password_hash'])) {
            Flash::error('La contraseña actual no es correcta.');
            Http::redirigir('/cuenta#clave', 303);
        }
        $error = Cuentas::validarClave($nueva, (string) ($_POST['clave_repetida'] ?? ''));
        if ($error !== null) {
            Flash::error($error);
        } else {
            CuentaAuth::cambiarClave((int) $this->cuenta['id'], $nueva);
            Flash::ok('Contraseña guardada. La próxima vez entrá con tu email y esta contraseña.');
        }
        Http::redirigir('/cuenta#clave', 303);
    }

    public function nuevo(): string
    {
        [$puede, $motivo] = Cuentas::puedePublicar($this->cuenta);
        if (!$puede) {
            Flash::error($motivo);
            Http::redirigir('/cuenta', 303);
        }
        return $this->formulario(null, [
            'tipo' => 'mensual', 'periodo' => 'mes', 'moneda' => 'ARS',
            'contacto_whatsapp' => $this->cuenta['whatsapp'],
            'contacto_telefono' => $this->cuenta['telefono'],
            'contacto_email' => $this->cuenta['email'],
        ], []);
    }

    public function crear(): string
    {
        [$puede, $motivo] = Cuentas::puedePublicar($this->cuenta);
        if (!$puede) {
            Flash::error($motivo);
            Http::redirigir('/cuenta', 303);
        }
        [$datos, $err] = AvisoService::validar($_POST, true);
        if ($err) {
            return $this->formulario(null, $_POST, $err);
        }
        $datos = Cuentas::aplicarPublicador($datos, $this->cuenta);
        $datos['cuenta_id'] = (int) $this->cuenta['id'];
        $directo = $this->cuenta['confiable'] && $datos['barrio_id'] !== null;

        $id = AvisoService::crear($datos, 'cuenta', $directo ? 'activo' : 'pendiente', Http::ip());
        Db::run('UPDATE avisos SET email_confirmado_en = NOW() WHERE id = ?', [$id]); // el email ya está probado por el ingreso
        foreach (Fotos::agregarSubidas($id, $_FILES['fotos'] ?? [], Config::int('FOTOS_MAX_POR_AVISO', 12)) as $error) {
            Flash::error($error);
        }
        if ($directo) {
            Flash::ok('¡Aviso publicado!');
        } else {
            Flash::ok('Recibimos tu aviso. Lo revisamos y lo publicamos, normalmente en menos de 24 horas.');
            Mail::avisarModeracion("Aviso para moderar (cuenta): {$datos['titulo']}", "{$this->cuenta['nombre']} ({$this->cuenta['email']}) cargó un aviso.\n\n" . url_abs('/admin/moderacion'));
        }
        Http::redirigir('/cuenta', 303);
    }

    public function editar(string $id): string
    {
        $aviso = $this->aviso($id);
        return $this->formulario($aviso, $aviso, []);
    }

    public function guardar(string $id): string
    {
        $aviso = $this->aviso($id);
        [$datos, $err] = AvisoService::validar($_POST, true);
        if ($aviso['estado'] !== 'pendiente' && $aviso['estado'] !== 'rechazado' && $datos['barrio_id'] === null) {
            $err['barrio_id'] = 'Elegí un barrio de la lista.';
        }
        if ($err) {
            return $this->formulario($aviso, $_POST, $err);
        }
        $datos = Cuentas::aplicarPublicador($datos, $this->cuenta);
        $datos['cuenta_id'] = (int) $this->cuenta['id'];
        AvisoService::actualizar((int) $aviso['id'], $datos);
        foreach (Fotos::agregarSubidas((int) $aviso['id'], $_FILES['fotos'] ?? [], Config::int('FOTOS_MAX_POR_AVISO', 12)) as $error) {
            Flash::error($error);
        }

        if ($aviso['estado'] === 'rechazado') {
            // Corregido después de un rechazo: vuelve a moderación.
            Db::run("UPDATE avisos SET estado = 'pendiente', motivo_estado = NULL WHERE id = ?", [$aviso['id']]);
            Flash::ok('Cambios guardados. El aviso volvió a revisión.');
        } elseif (!$this->cuenta['confiable'] && in_array($aviso['estado'], ['activo', 'pausado', 'alquilado'], true)) {
            Db::run('UPDATE avisos SET cambios_sin_revisar_en = NOW() WHERE id = ?', [$aviso['id']]);
            Flash::ok('Cambios guardados y publicados.');
        } else {
            Flash::ok('Cambios guardados.');
        }
        Http::redirigir('/cuenta', 303);
    }

    public function fotos(string $id): string
    {
        $aviso = $this->aviso($id);
        Fotos::actualizarDesdeFormulario((int) $aviso['id'], $_POST);
        if (!$this->cuenta['confiable'] && $aviso['estado'] === 'activo') {
            Db::run('UPDATE avisos SET cambios_sin_revisar_en = NOW() WHERE id = ?', [$aviso['id']]);
        }
        Flash::ok('Fotos actualizadas.');
        Http::redirigir('/cuenta/avisos/' . (int) $aviso['id'] . '#fotos', 303);
    }

    public function accion(string $id, string $accion): string
    {
        $aviso = $this->aviso($id);
        $avisoId = (int) $aviso['id'];
        $propio = in_array((string) $aviso['motivo_estado'], Cuentas::MOTIVOS_DEL_ANUNCIANTE, true);
        try {
            switch ($accion) {
                case 'pausar':
                    $this->exigir($aviso['estado'] === 'activo');
                    AvisoService::cambiarEstado($avisoId, 'pausado', 'Pausado por el anunciante');
                    Flash::ok('Aviso pausado. Podés reactivarlo cuando quieras.');
                    break;
                case 'alquilado':
                    $this->exigir(in_array($aviso['estado'], ['activo', 'pausado'], true));
                    AvisoService::cambiarEstado($avisoId, 'alquilado', 'Marcado alquilado por el anunciante');
                    Flash::ok('¡Felicitaciones! Marcamos el aviso como alquilado.');
                    break;
                case 'reactivar':
                    // Solo lo que pausó o dio de baja el propio anunciante; lo que pausó el sitio lo revisa el equipo.
                    $this->exigir(in_array($aviso['estado'], ['pausado', 'alquilado'], true) && $propio);
                    // Un pausado ya cuenta para el límite; uno alquilado vuelve a ocupar un lugar.
                    [$puede, $motivo] = $aviso['estado'] === 'alquilado' ? Cuentas::puedePublicar($this->cuenta) : [true, null];
                    if (!$puede) {
                        throw new \RuntimeException($motivo);
                    }
                    AvisoService::cambiarEstado($avisoId, 'activo');
                    Flash::ok('Aviso publicado de nuevo.');
                    break;
                case 'disponible':
                    $this->exigir($aviso['estado'] === 'activo');
                    ColaVerificacion::confirmar($avisoId, 'link');
                    Flash::ok('¡Gracias! Confirmamos que sigue disponible.');
                    break;
            }
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }
        Http::redirigir('/cuenta', 303);
    }

    /** @return array<string, mixed> el aviso, solo si es de esta cuenta */
    private function aviso(string $id): array
    {
        return Db::fila('SELECT * FROM avisos WHERE id = ? AND cuenta_id = ?', [(int) $id, $this->cuenta['id']]) ?? throw new NoEncontrado();
    }

    private function exigir(bool $condicion): void
    {
        if (!$condicion) {
            throw new \RuntimeException('Esa acción no está disponible para este aviso.');
        }
    }

    /** @param array<string, mixed>|null $aviso @param array<string, mixed> $v @param array<string, string> $err */
    private function formulario(?array $aviso, array $v, array $err): string
    {
        if ($err) {
            http_response_code(422);
        }
        return $this->render('cuenta/aviso-form', $aviso ? 'Editar aviso' : 'Nuevo aviso', [
            'cuenta' => $this->cuenta,
            'aviso' => $aviso,
            'v' => $v,
            'err' => $err,
            'fotos' => $aviso ? (new AvisoRepo())->fotos((int) $aviso['id']) : [],
            'barrios' => (new BarrioRepo())->activos(),
            'flash' => Flash::sacar(),
        ]);
    }

    /** @param array<string, mixed> $datos */
    private function render(string $plantilla, string $titulo, array $datos): string
    {
        return View::render($plantilla, $datos + [
            'meta' => new Meta(title: $titulo, description: 'Panel del anunciante.', indexar: false),
        ]);
    }
}
