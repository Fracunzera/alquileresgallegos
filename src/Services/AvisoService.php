<?php

declare(strict_types=1);

namespace App\Services;

use App\Catalogo;
use App\Db;

/**
 * Alta, edición y cambios de estado de avisos. Única puerta de escritura: la usan
 * el panel y el formulario público, así las reglas son las mismas en los dos lados.
 */
final class AvisoService
{
    /** Rectángulo amplio alrededor de Río Gallegos: descarta pins puestos por error en otra ciudad. */
    private const LIMITES = ['lat_min' => -51.80, 'lat_max' => -51.45, 'lng_min' => -69.50, 'lng_max' => -68.95];

    /** Columnas que se pueden escribir desde un formulario. */
    private const CAMPOS = [
        'titulo', 'tipo', 'categoria', 'ambientes', 'dormitorios', 'banos', 'acepta_mascotas', 'acepta_ninos', 'barrio_id', 'barrio_sugerido',
        'direccion', 'mostrar_direccion', 'lat', 'lng', 'precio', 'moneda', 'periodo', 'expensas', 'descripcion',
        'contacto_whatsapp', 'contacto_telefono', 'contacto_email', 'mostrar_email', 'publicador', 'inmobiliaria_id',
        'destacado', 'fuente_url', 'cuenta_id',
    ];

    /**
     * Valida la entrada de un formulario.
     *
     * @param array<string, mixed> $entrada normalmente $_POST
     * @param bool $publico true para /publicar (menos campos, reglas más estrictas)
     * @return array{0: array<string, mixed>, 1: array<string, string>} [datos limpios, errores por campo]
     */
    public static function validar(array $entrada, bool $publico): array
    {
        $txt = static fn (string $k): string => trim((string) ($entrada[$k] ?? ''));
        $entero = static function (string $k, int $max) use ($txt): ?int {
            $v = preg_replace('/\D/', '', $txt($k));
            return $v === '' ? null : min((int) $v, $max);
        };
        $d = [];
        $err = [];

        $d['titulo'] = mb_substr(preg_replace('/\s+/u', ' ', $txt('titulo')), 0, 140);
        if (mb_strlen($d['titulo']) < 10) {
            $err['titulo'] = 'Escribí un título de al menos 10 caracteres (ej: "Departamento 2 ambientes en el centro").';
        }

        $d['tipo'] = $txt('tipo');
        if (!isset(Catalogo::TIPOS[$d['tipo']])) {
            $err['tipo'] = 'Elegí si es mensual o temporario.';
        }
        $d['categoria'] = $txt('categoria');
        if (!isset(Catalogo::CATEGORIAS[$d['categoria']])) {
            $err['categoria'] = 'Elegí el tipo de propiedad.';
        }

        $d['ambientes'] = $entero('ambientes', 30);
        $d['dormitorios'] = $entero('dormitorios', 30);
        $d['banos'] = $entero('banos', 30);
        foreach (['acepta_mascotas', 'acepta_ninos'] as $campo) {
            $d[$campo] = isset(Catalogo::ACEPTA[$txt($campo)]) ? $txt($campo) : null;
        }

        // Barrio: de la lista. En /publicar se puede elegir "otro" y escribirlo; se asigna al moderar.
        $barrio = $txt('barrio_id');
        $d['barrio_id'] = ctype_digit($barrio) && Db::valor('SELECT id FROM barrios WHERE id = ?', [(int) $barrio]) !== null ? (int) $barrio : null;
        $d['barrio_sugerido'] = null;
        if ($d['barrio_id'] === null) {
            $sugerido = mb_substr($txt('barrio_sugerido'), 0, 80);
            if ($publico && $barrio === 'otro' && $sugerido !== '') {
                $d['barrio_sugerido'] = $sugerido;
            } else {
                $err['barrio_id'] = $publico && $barrio === 'otro' ? 'Escribí el nombre del barrio.' : 'Elegí el barrio.';
            }
        }

        $d['direccion'] = mb_substr($txt('direccion'), 0, 160) ?: null;
        $d['mostrar_direccion'] = !empty($entrada['mostrar_direccion']) && $d['direccion'] !== null ? 1 : 0;

        $lat = $txt('lat');
        $lng = $txt('lng');
        $d['lat'] = $d['lng'] = null;
        if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
            $lat = (float) $lat;
            $lng = (float) $lng;
            if ($lat >= self::LIMITES['lat_min'] && $lat <= self::LIMITES['lat_max'] && $lng >= self::LIMITES['lng_min'] && $lng <= self::LIMITES['lng_max']) {
                $d['lat'] = round($lat, 6);
                $d['lng'] = round($lng, 6);
            } else {
                $err['lat'] = 'La ubicación marcada queda fuera de Río Gallegos.';
            }
        }

        $d['precio'] = $entero('precio', 4_000_000_000) ?: null;
        $d['moneda'] = $txt('moneda') === 'USD' ? 'USD' : 'ARS';
        $periodo = $txt('periodo');
        $d['periodo'] = in_array($periodo, ['mes', 'dia'], true) ? $periodo : ($d['tipo'] === 'temporario' ? 'dia' : 'mes');
        $d['expensas'] = $entero('expensas', 100_000_000);
        if ($publico && $d['precio'] === null) {
            $err['precio'] = 'Indicá el precio: los avisos con precio reciben muchas más consultas.';
        }

        $d['descripcion'] = trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\r\n", "\n", $txt('descripcion'))));
        $minimo = $publico ? 80 : 30;
        if (mb_strlen($d['descripcion']) < $minimo) {
            $err['descripcion'] = "Describí la propiedad con al menos {$minimo} caracteres: ambientes, calefacción, requisitos, qué incluye.";
        }
        $d['descripcion'] = mb_substr($d['descripcion'], 0, 8000);

        $wa = $txt('contacto_whatsapp');
        $d['contacto_whatsapp'] = $wa !== '' ? Telefono::whatsapp($wa) : null;
        if ($wa !== '' && $d['contacto_whatsapp'] === null) {
            $err['contacto_whatsapp'] = 'Revisá el número de WhatsApp (ej: 2966 15 123456).';
        }
        $d['contacto_telefono'] = mb_substr($txt('contacto_telefono'), 0, 30) ?: null;
        $email = mb_strtolower($txt('contacto_email'));
        $d['contacto_email'] = $email !== '' ? $email : null;
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $err['contacto_email'] = 'Revisá el email.';
        }
        if ($d['contacto_whatsapp'] === null && $d['contacto_telefono'] === null && $d['contacto_email'] === null && !isset($err['contacto_whatsapp'])) {
            $err['contacto_whatsapp'] = 'Dejá al menos un dato de contacto (idealmente WhatsApp).';
        }
        if ($publico && $d['contacto_email'] === null) {
            $err['contacto_email'] = 'Necesitamos un email para contactarte por el aviso y preguntarte cada tanto si sigue disponible.';
        }
        $d['mostrar_email'] = empty($entrada['ocultar_email']) ? 1 : 0;
        if (!$d['mostrar_email'] && $d['contacto_whatsapp'] === null && $d['contacto_telefono'] === null && !isset($err['contacto_whatsapp'])) {
            $err['contacto_whatsapp'] = 'Si no mostrás el email, dejá un WhatsApp o teléfono para que te contacten.';
        }

        if ($publico) {
            $d['publicador'] = 'particular';
            $d['inmobiliaria_id'] = null;
            $d['destacado'] = 0;
            $d['fuente_url'] = null;
        } else {
            $d['publicador'] = $txt('publicador') === 'inmobiliaria' ? 'inmobiliaria' : 'particular';
            $inmo = $txt('inmobiliaria_id');
            $d['inmobiliaria_id'] = $d['publicador'] === 'inmobiliaria' && ctype_digit($inmo) ? (int) $inmo : null;
            if ($d['publicador'] === 'inmobiliaria' && ($d['inmobiliaria_id'] === null || Db::valor('SELECT id FROM inmobiliarias WHERE id = ?', [$d['inmobiliaria_id']]) === null)) {
                $err['inmobiliaria_id'] = 'Elegí la inmobiliaria.';
            }
            $d['destacado'] = !empty($entrada['destacado']) ? 1 : 0;
            $fuente = mb_substr($txt('fuente_url'), 0, 500);
            $d['fuente_url'] = $fuente !== '' ? $fuente : null;
            if ($fuente !== '' && !preg_match('#^https?://#i', $fuente)) {
                $err['fuente_url'] = 'La fuente tiene que ser un link (https://…).';
            }
        }

        return [$d, $err];
    }

    /**
     * @param array<string, mixed> $datos salida de validar()
     */
    public static function crear(array $datos, string $origen, string $estado, ?string $ip = null): int
    {
        $fila = array_intersect_key($datos, array_flip(self::CAMPOS));
        $fila['slug'] = Slugger::slug($datos['titulo']);
        $fila['origen'] = $origen;
        $fila['estado'] = $estado;
        $fila['ip_origen'] = $ip !== null ? @inet_pton($ip) ?: null : null;
        if ($estado === 'activo') {
            // Lo carga el admin después de relevarlo: cuenta como verificado hoy.
            $fila['publicado_en'] = date('Y-m-d H:i:s');
            $fila['ultima_verificacion'] = date('Y-m-d H:i:s');
        }

        $columnas = array_keys($fila);
        Db::run(
            'INSERT INTO avisos (' . implode(', ', $columnas) . ') VALUES (' . implode(', ', array_fill(0, count($columnas), '?')) . ')',
            array_values($fila)
        );
        return (int) Db::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $datos salida de validar() */
    public static function actualizar(int $id, array $datos): void
    {
        $fila = array_intersect_key($datos, array_flip(self::CAMPOS));
        $fila['slug'] = Slugger::slug($datos['titulo']);
        $sets = implode(', ', array_map(fn ($c) => "{$c} = ?", array_keys($fila)));
        Db::run("UPDATE avisos SET {$sets} WHERE id = ?", [...array_values($fila), $id]);
    }

    /**
     * Cambia el estado y deja registro en verificaciones cuando corresponde.
     *
     * @throws \RuntimeException si no se puede activar (ej. falta asignar barrio)
     */
    public static function cambiarEstado(int $id, string $estado, ?string $motivo = null, ?int $adminId = null): void
    {
        if (!isset(Catalogo::ESTADOS[$estado])) {
            throw new \InvalidArgumentException("Estado inválido: {$estado}");
        }
        $aviso = Db::fila('SELECT id, estado, barrio_id, publicado_en FROM avisos WHERE id = ?', [$id]) ?? throw new \RuntimeException('El aviso no existe.');
        if ($estado === 'activo' && $aviso['barrio_id'] === null) {
            throw new \RuntimeException('Asigná un barrio antes de publicar el aviso.');
        }

        $extra = '';
        if ($estado === 'activo') {
            // Al (re)activar cuenta como verificado y se cierra cualquier pedido de confirmación pendiente.
            $extra = ', publicado_en = COALESCE(publicado_en, NOW()), ultima_verificacion = NOW(), verificacion_solicitada_en = NULL,'
                . ' recordatorio_enviado_en = NULL, token_gestion = NULL';
        }
        Db::run("UPDATE avisos SET estado = ?, motivo_estado = ?{$extra} WHERE id = ?", [$estado, $motivo, $id]);

        $accion = match ($estado) {
            'alquilado' => 'alquilado',
            'pausado' => 'pausado',
            default => null,
        };
        if ($accion !== null) {
            self::registrarVerificacion($id, $accion, 'manual', $motivo, $adminId);
        }
    }

    public static function registrarVerificacion(int $avisoId, string $accion, string $canal, ?string $nota = null, ?int $adminId = null): void
    {
        Db::run(
            'INSERT INTO verificaciones (aviso_id, accion, canal, nota, admin_id) VALUES (?, ?, ?, ?, ?)',
            [$avisoId, $accion, $canal, $nota !== null ? mb_substr($nota, 0, 255) : null, $adminId]
        );
    }

    public static function borrar(int $id): void
    {
        Fotos::borrarDeAviso($id);
        Db::run('DELETE FROM avisos WHERE id = ?', [$id]);
    }
}
