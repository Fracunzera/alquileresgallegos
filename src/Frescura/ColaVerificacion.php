<?php

declare(strict_types=1);

namespace App\Frescura;

use App\Catalogo;
use App\Config;
use App\Db;
use App\Mail\Email;
use App\Mail\Mail;
use App\Services\AvisoService;

/**
 * Frescura de avisos:
 *  - un aviso activo sin verificar hace más de FRESCURA_DIAS_VERIFICACION días (30) entra en la cola;
 *  - "solicitar" genera un link único /verificar/{id}/{token} y lo manda (o lo deja listo para mandar);
 *  - FRESCURA_DIAS_RECORDATORIO días antes del vencimiento el cron manda un recordatorio con el mismo link;
 *  - si pasan FRESCURA_DIAS_GRACIA días (10) sin respuesta y FRESCURA_AUTODESPUBLICAR=1, el cron lo
 *    pausa (sale del sitio) y le avisa al anunciante, que lo puede volver a publicar con un clic.
 */
final class ColaVerificacion
{
    public const MOTIVO_SIN_RESPUESTA = 'Sin respuesta al pedido de verificación';

    private const REFERENCIA = 'COALESCE(a.ultima_verificacion, a.publicado_en, a.creado_en)';

    public static function diasVerificacion(): int
    {
        return Config::int('FRESCURA_DIAS_VERIFICACION', 30);
    }

    public static function diasGracia(): int
    {
        return Config::int('FRESCURA_DIAS_GRACIA', 10);
    }

    /** Días antes de pausar en que se manda el recordatorio (0 = sin recordatorio). */
    public static function diasRecordatorio(): int
    {
        $dias = Config::int('FRESCURA_DIAS_RECORDATORIO', 3);
        return $dias > 0 && $dias < self::diasGracia() ? $dias : 0;
    }

    /** @return list<array<string, mixed>> avisos activos vencidos, los más viejos primero */
    public static function pendientes(): array
    {
        return Db::filas(
            'SELECT a.*, b.nombre AS barrio_nombre, i.nombre AS inmobiliaria_nombre, ' . self::REFERENCIA . ' AS referencia
             FROM avisos a LEFT JOIN barrios b ON b.id = a.barrio_id LEFT JOIN inmobiliarias i ON i.id = a.inmobiliaria_id
             WHERE a.estado = \'activo\' AND ' . self::REFERENCIA . ' < NOW() - INTERVAL ? DAY
             ORDER BY a.verificacion_solicitada_en IS NOT NULL, referencia',
            [self::diasVerificacion()]
        );
    }

    public static function contar(): int
    {
        return (int) Db::valor(
            "SELECT COUNT(*) FROM avisos a WHERE a.estado = 'activo' AND " . self::REFERENCIA . ' < NOW() - INTERVAL ? DAY',
            [self::diasVerificacion()]
        );
    }

    /**
     * Genera el link de confirmación, marca la solicitud y la manda por el notificador configurado.
     *
     * @return array{link: string, mensaje: string, enviado: bool}
     */
    public static function solicitar(int $avisoId, ?int $adminId = null): array
    {
        $aviso = Db::fila('SELECT * FROM avisos WHERE id = ?', [$avisoId]) ?? throw new \RuntimeException('El aviso no existe.');
        // El token se deriva de la fecha del pedido: el recordatorio y el aviso de pausa reusan el mismo link.
        $aviso['verificacion_solicitada_en'] = date('Y-m-d H:i:s');
        $token = self::token($aviso);
        Db::run(
            'UPDATE avisos SET token_gestion = ?, verificacion_solicitada_en = ?, recordatorio_enviado_en = NULL WHERE id = ?',
            [hash('sha256', $token), $aviso['verificacion_solicitada_en'], $avisoId]
        );
        $link = self::link($aviso);
        $mensaje = self::mensaje($aviso, $link);
        $enviado = self::notificador()->enviar($aviso, $link, $mensaje);
        AvisoService::registrarVerificacion($avisoId, 'solicitada', $enviado ? self::canal() : 'manual', null, $adminId);
        return ['link' => $link, 'mensaje' => $mensaje, 'enviado' => $enviado];
    }

    public static function confirmar(int $avisoId, string $canal, ?int $adminId = null): void
    {
        Db::run(
            "UPDATE avisos SET ultima_verificacion = NOW(), verificacion_solicitada_en = NULL, recordatorio_enviado_en = NULL,
             token_gestion = NULL WHERE id = ? AND estado = 'activo'",
            [$avisoId]
        );
        AvisoService::registrarVerificacion($avisoId, 'confirmada', $canal, null, $adminId);
    }

    /** Deshace un pedido que no se pudo mandar, para que no corra el plazo de pausa sin que el anunciante lo sepa. */
    public static function anularSolicitud(int $avisoId): void
    {
        Db::run('UPDATE avisos SET verificacion_solicitada_en = NULL, recordatorio_enviado_en = NULL, token_gestion = NULL WHERE id = ?', [$avisoId]);
    }

    /** @return array<string, mixed>|null el aviso si el token es válido */
    public static function porToken(int $avisoId, string $token): ?array
    {
        $aviso = Db::fila('SELECT a.*, b.nombre AS barrio_nombre FROM avisos a LEFT JOIN barrios b ON b.id = a.barrio_id WHERE a.id = ?', [$avisoId]);
        if ($aviso === null || $aviso['token_gestion'] === null || !hash_equals($aviso['token_gestion'], hash('sha256', $token))) {
            return null;
        }
        return $aviso;
    }

    /** Lo pausó el cron por no responder: el anunciante lo puede volver a publicar desde el link o el panel. */
    public static function pausadoSinRespuesta(array $aviso): bool
    {
        return $aviso['estado'] === 'pausado' && $aviso['motivo_estado'] === self::MOTIVO_SIN_RESPUESTA;
    }

    /**
     * Manda un recordatorio a los que no respondieron y están a FRESCURA_DIAS_RECORDATORIO días de
     * ser pausados. Solo por email (con el aviso manual no hay a quién recordarle). Lo llama el cron.
     *
     * @return list<int> ids recordados
     */
    public static function recordarPorVencer(): array
    {
        $dias = self::diasRecordatorio();
        if ($dias === 0 || self::canal() !== 'email') {
            return [];
        }
        $avisos = Db::filas(
            "SELECT * FROM avisos WHERE estado = 'activo' AND recordatorio_enviado_en IS NULL
             AND verificacion_solicitada_en < NOW() - INTERVAL ? DAY",
            [self::diasGracia() - $dias]
        );
        $ids = [];
        foreach ($avisos as $aviso) {
            $link = self::link($aviso);
            // Los pedidos hechos antes de este cambio tienen otro token (aleatorio): se reemplaza por el derivado.
            Db::run('UPDATE avisos SET recordatorio_enviado_en = NOW(), token_gestion = ? WHERE id = ?', [hash('sha256', self::token($aviso)), $aviso['id']]);
            $quedan = max(1, self::diasGracia() - (int) floor((time() - strtotime((string) $aviso['verificacion_solicitada_en'])) / 86400));
            $enviado = self::enviarEmail(
                $aviso,
                'Recordatorio: ¿tu aviso sigue disponible?',
                [
                    "Hola! Hace unos días te preguntamos por tu aviso \"{$aviso['titulo']}\" (" . Catalogo::precio($aviso) . ') y todavía no nos contestaste.',
                    "Si no respondés, en {$quedan} " . ($quedan === 1 ? 'día' : 'días') . ' lo vamos a sacar del sitio para que nadie consulte por algo que ya no está.',
                ],
                $link
            );
            if ($enviado) {
                AvisoService::registrarVerificacion((int) $aviso['id'], 'recordatorio', 'email');
                $ids[] = (int) $aviso['id'];
            }
        }
        return $ids;
    }

    /**
     * Pausa los avisos que no respondieron dentro del plazo y le avisa al anunciante. Lo llama el cron.
     * No se borran: dejan de verse en el sitio, pero el anunciante los puede volver a publicar.
     *
     * @return list<int> ids pausados
     */
    public static function despublicarSinRespuesta(): array
    {
        $avisos = Db::filas(
            "SELECT * FROM avisos WHERE estado = 'activo' AND verificacion_solicitada_en < NOW() - INTERVAL ? DAY",
            [self::diasGracia()]
        );
        $ids = [];
        foreach ($avisos as $aviso) {
            $id = (int) $aviso['id'];
            // El link sigue sirviendo para volver a publicarlo (o marcarlo alquilado) hasta que responda.
            Db::run(
                "UPDATE avisos SET estado = 'pausado', motivo_estado = ?, token_gestion = ? WHERE id = ?",
                [self::MOTIVO_SIN_RESPUESTA, hash('sha256', self::token($aviso)), $id]
            );
            AvisoService::registrarVerificacion($id, 'despublicado_sin_respuesta', 'cron', 'Sin respuesta en ' . self::diasGracia() . ' días');
            if (self::canal() === 'email') {
                $panel = $aviso['cuenta_id'] !== null ? ' También podés hacerlo desde tu cuenta: ' . url_abs('/cuenta') : '';
                self::enviarEmail(
                    $aviso,
                    'Sacamos tu aviso del sitio: ' . $aviso['titulo'],
                    [
                        "Hola! Como no recibimos respuesta, sacamos del sitio tu aviso \"{$aviso['titulo']}\" (" . Catalogo::precio($aviso) . ').',
                        'Si todavía no lo alquilaste, volvé a publicarlo con un clic desde el botón de abajo.' . $panel,
                    ],
                    self::link($aviso),
                    'Sigue disponible: volver a publicar'
                );
            }
            $ids[] = $id;
        }
        return $ids;
    }

    /** Texto del pedido (email en texto plano y mensaje de WhatsApp para mandar a mano). */
    public static function mensaje(array $aviso, string $link): string
    {
        return implode("\n\n", self::parrafosPedido($aviso)) . "\n\n¿Ya lo alquilaste o sigue disponible? Contestanos con un clic acá: {$link}\n\n"
            . self::advertencia() . ' ¡Gracias!';
    }

    /**
     * Email del pedido de confirmación con dos botones: "Sigue disponible" y "Ya lo alquilé".
     *
     * @param array<string, mixed> $aviso
     */
    public static function emailPedido(array $aviso, string $para, string $link): Email
    {
        $parrafos = [...self::parrafosPedido($aviso), self::advertencia(), '¿Ya lo alquilaste o sigue disponible? Contestanos con un clic:'];
        return new Email(
            $para,
            '¿Ya alquilaste? ' . $aviso['titulo'],
            self::mensaje($aviso, $link),
            null,
            Email::htmlConBotones($parrafos, self::botones($link), self::pie())
        );
    }

    /**
     * @param array<string, mixed> $aviso
     * @return list<string>
     */
    private static function parrafosPedido(array $aviso): array
    {
        $parrafos = ['Hola! Te escribimos de ' . app_nombre() . " por tu aviso \"{$aviso['titulo']}\" (" . Catalogo::precio($aviso) . ').'];
        $stats = \App\Services\Estadisticas::resumen([(int) $aviso['id']])[(int) $aviso['id']];
        if ($stats['vistas'] > 0) {
            $parrafos[] = "En los últimos 30 días lo vieron {$stats['vistas']} personas y te consultaron {$stats['consultas']} veces desde el sitio.";
        }
        return $parrafos;
    }

    private static function advertencia(): string
    {
        return 'Si no recibimos respuesta en ' . self::diasGracia() . ' días lo vamos a sacar del sitio para que nadie consulte por algo que ya no está.';
    }

    /** @param array<string, mixed> $aviso */
    public static function link(array $aviso): string
    {
        return url_abs('/verificar/' . (int) $aviso['id'] . '/' . self::token($aviso));
    }

    /** Email al que se manda: el de la cuenta (confirmado) si el aviso es de una cuenta; si no, el de contacto. */
    public static function destinatario(array $aviso): ?string
    {
        if ($aviso['cuenta_id'] !== null) {
            $email = Db::valor("SELECT email FROM cuentas WHERE id = ? AND estado = 'activa'", [$aviso['cuenta_id']]);
            if ($email !== null) {
                return (string) $email;
            }
        }
        return !empty($aviso['contacto_email']) ? (string) $aviso['contacto_email'] : null;
    }

    public static function notificador(): Notificador
    {
        return match (self::canal()) {
            'email' => new EmailNotificador(),
            'whatsapp' => new WhatsappNotificador(),
            default => new NullNotificador(),
        };
    }

    private static function canal(): string
    {
        $canal = (string) Config::get('NOTIFICADOR', 'null');
        return in_array($canal, ['email', 'whatsapp'], true) ? $canal : 'null';
    }

    /** @param array<string, mixed> $aviso con verificacion_solicitada_en */
    private static function token(array $aviso): string
    {
        $base = 'verificar|' . (int) $aviso['id'] . '|' . $aviso['verificacion_solicitada_en'];
        return substr(hash_hmac('sha256', $base, Config::clave()), 0, 48);
    }

    /** @return array<string, string> texto => url */
    private static function botones(string $link, string $disponible = 'Sigue disponible'): array
    {
        return [$disponible => $link . '?r=disponible', 'Ya lo alquilé' => $link . '?r=alquilado'];
    }

    private static function pie(): string
    {
        return 'Te escribimos porque publicaste este aviso en ' . app_nombre() . '. Preguntamos cada '
            . self::diasVerificacion() . ' días para que el sitio muestre solo propiedades disponibles.';
    }

    /**
     * @param array<string, mixed> $aviso
     * @param list<string> $parrafos
     */
    private static function enviarEmail(array $aviso, string $asunto, array $parrafos, string $link, string $disponible = 'Sigue disponible'): bool
    {
        $para = self::destinatario($aviso);
        if ($para === null) {
            return false;
        }
        $texto = implode("\n\n", $parrafos) . "\n\nContestanos con un clic: {$link}";
        return Mail::enviar(new Email($para, $asunto, $texto, null, Email::htmlConBotones($parrafos, self::botones($link, $disponible), self::pie())));
    }
}
