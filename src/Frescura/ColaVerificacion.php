<?php

declare(strict_types=1);

namespace App\Frescura;

use App\Catalogo;
use App\Config;
use App\Db;
use App\Services\AvisoService;

/**
 * Frescura de avisos:
 *  - un aviso activo sin verificar hace más de FRESCURA_DIAS_VERIFICACION días entra en la cola;
 *  - "solicitar" genera un link único /verificar/{id}/{token} y lo manda (o lo deja listo para mandar);
 *  - si pasan FRESCURA_DIAS_GRACIA días sin respuesta y FRESCURA_AUTODESPUBLICAR=1, el cron lo pausa.
 */
final class ColaVerificacion
{
    private const REFERENCIA = 'COALESCE(a.ultima_verificacion, a.publicado_en, a.creado_en)';

    public static function diasVerificacion(): int
    {
        return Config::int('FRESCURA_DIAS_VERIFICACION', 15);
    }

    public static function diasGracia(): int
    {
        return Config::int('FRESCURA_DIAS_GRACIA', 7);
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
        $token = bin2hex(random_bytes(24));
        Db::run(
            'UPDATE avisos SET token_gestion = ?, verificacion_solicitada_en = NOW() WHERE id = ?',
            [hash('sha256', $token), $avisoId]
        );
        $link = url_abs("/verificar/{$avisoId}/{$token}");
        $mensaje = self::mensaje($aviso, $link);
        $enviado = self::notificador()->enviar($aviso, $link, $mensaje);
        AvisoService::registrarVerificacion($avisoId, 'solicitada', $enviado ? self::canal() : 'manual', null, $adminId);
        return ['link' => $link, 'mensaje' => $mensaje, 'enviado' => $enviado];
    }

    public static function confirmar(int $avisoId, string $canal, ?int $adminId = null): void
    {
        Db::run(
            "UPDATE avisos SET ultima_verificacion = NOW(), verificacion_solicitada_en = NULL, token_gestion = NULL WHERE id = ? AND estado = 'activo'",
            [$avisoId]
        );
        AvisoService::registrarVerificacion($avisoId, 'confirmada', $canal, null, $adminId);
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

    /**
     * Pausa los avisos que no respondieron dentro del plazo. Lo llama el cron.
     *
     * @return list<int> ids pausados
     */
    public static function despublicarSinRespuesta(): array
    {
        $ids = array_map('intval', array_column(Db::filas(
            "SELECT id FROM avisos WHERE estado = 'activo' AND verificacion_solicitada_en < NOW() - INTERVAL ? DAY",
            [self::diasGracia()]
        ), 'id'));
        foreach ($ids as $id) {
            Db::run(
                "UPDATE avisos SET estado = 'pausado', motivo_estado = 'Sin respuesta al pedido de verificación' WHERE id = ?",
                [$id]
            );
            AvisoService::registrarVerificacion($id, 'despublicado_sin_respuesta', 'cron', 'Sin respuesta en ' . self::diasGracia() . ' días');
        }
        return $ids;
    }

    /** @param array<string, mixed> $aviso */
    public static function mensaje(array $aviso, string $link): string
    {
        $stats = \App\Services\Estadisticas::resumen([(int) $aviso['id']])[(int) $aviso['id']];
        $actividad = $stats['vistas'] > 0
            ? "En los últimos 30 días lo vieron {$stats['vistas']} personas y te consultaron {$stats['consultas']} veces desde el sitio.\n\n"
            : '';
        return "Hola! Te escribimos de " . app_nombre() . " por tu aviso \"{$aviso['titulo']}\" ("
            . Catalogo::precio($aviso) . ").\n\n{$actividad}¿Sigue disponible? Confirmalo con un clic acá: {$link}\n\n"
            . 'Si no recibimos respuesta en ' . self::diasGracia() . ' días lo vamos a pausar para que nadie consulte por algo que ya no está. ¡Gracias!';
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
}
