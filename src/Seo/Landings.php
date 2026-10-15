<?php

declare(strict_types=1);

namespace App\Seo;

use App\Catalogo;

/**
 * Páginas de aterrizaje indexables. Cada una tiene su propio title, description e intro:
 * no son filtros del buscador sino páginas con contenido propio.
 *
 * Los textos de title/description admiten {total} y {desde}, que se completan con datos reales.
 */
final class Landings
{
    /** Slugs que un barrio no puede usar porque chocarían con una landing fija. */
    public const SLUGS_RESERVADOS = ['mensuales'];

    /** @return array<string, array<string, mixed>> */
    public static function fijas(): array
    {
        return [
            '/alquileres-mensuales-rio-gallegos' => [
                'filtro' => ['tipo' => 'mensual'],
                'h1' => 'Alquileres mensuales en Río Gallegos',
                'title' => 'Alquileres mensuales en Río Gallegos: {total} casas y departamentos',
                'description' => '{total} casas, departamentos y monoambientes en alquiler mensual en Río Gallegos{desde}. Precio y expensas a la vista, contacto directo por WhatsApp.',
                'intro' => 'Casas, departamentos y monoambientes en alquiler mensual en Río Gallegos, publicados por dueños directos y por las inmobiliarias de la ciudad. En cada aviso vas a ver el precio del alquiler y, por separado, el valor de las expensas, para que sepas cuánto vas a pagar de verdad cada mes. Escribile al anunciante por WhatsApp directamente desde el aviso, sin registrarte.',
                'periodo' => 'mes',
            ],
            '/alquiler-temporario-rio-gallegos' => [
                'filtro' => ['tipo' => 'temporario'],
                'h1' => 'Alquiler temporario en Río Gallegos',
                'title' => 'Alquiler temporario en Río Gallegos: {total} departamentos y casas por día',
                'description' => '{total} alquileres temporarios en Río Gallegos{desde}. Departamentos y casas por día para viajes de trabajo, trámites o turismo. Consultá por WhatsApp.',
                'intro' => 'Departamentos, casas y monoambientes para alquilar por día en Río Gallegos. Una alternativa al hotel si venís por trabajo, por un trámite, a visitar a la familia o de paso en un viaje por la Patagonia. Consultá disponibilidad y precio directamente con el dueño o la inmobiliaria por WhatsApp.',
                'periodo' => 'dia',
            ],
            '/departamentos-alquiler-rio-gallegos' => [
                'filtro' => ['categoria' => 'departamento'],
                'h1' => 'Departamentos en alquiler en Río Gallegos',
                'title' => 'Departamentos en alquiler en Río Gallegos: {total} avisos',
                'description' => '{total} departamentos en alquiler en Río Gallegos{desde}, mensuales y temporarios. Filtrá por barrio, dormitorios y precio. Contacto directo por WhatsApp.',
                'intro' => 'Departamentos en alquiler en Río Gallegos, de uno, dos y tres dormitorios, por mes o por día. Filtrá por barrio, cantidad de dormitorios y precio máximo, y compará siempre el alquiler sumando las expensas: en muchos edificios de la ciudad pesan bastante en el total.',
                'periodo' => 'mes',
            ],
            '/casas-alquiler-rio-gallegos' => [
                'filtro' => ['categoria' => 'casa'],
                'h1' => 'Casas en alquiler en Río Gallegos',
                'title' => 'Casas en alquiler en Río Gallegos: {total} avisos',
                'description' => '{total} casas en alquiler en Río Gallegos{desde}. Avisos de dueños directos e inmobiliarias, con fotos, barrio y contacto por WhatsApp.',
                'intro' => 'Casas en alquiler en Río Gallegos publicadas por dueños directos e inmobiliarias. Cada aviso indica el barrio, la cantidad de ambientes y dormitorios, si tiene expensas y cómo contactar al anunciante. Si buscás algo puntual, filtrá por barrio o por cantidad de dormitorios.',
                'periodo' => 'mes',
            ],
            '/monoambientes-rio-gallegos' => [
                'filtro' => ['categoria' => 'monoambiente'],
                'h1' => 'Monoambientes en alquiler en Río Gallegos',
                'title' => 'Monoambientes en alquiler en Río Gallegos: {total} avisos',
                'description' => '{total} monoambientes en alquiler en Río Gallegos{desde}. La opción más económica para vivir solo o por temporada. Contacto directo por WhatsApp.',
                'intro' => 'Monoambientes en alquiler en Río Gallegos, mensuales y temporarios. Son la opción más accesible para estudiantes, para quien llega a la ciudad por trabajo o para quien busca su primer lugar propio. Revisá en cada aviso si las expensas están incluidas y si se aceptan mascotas.',
                'periodo' => 'mes',
            ],
        ];
    }

    /**
     * Landing de un barrio, armada desde sus datos.
     *
     * @param array<string, mixed> $barrio
     * @return array<string, mixed>
     */
    public static function barrio(array $barrio): array
    {
        $nombre = $barrio['nombre'];
        return [
            'filtro' => ['barrio_id' => (int) $barrio['id']],
            'h1' => "Alquileres en {$nombre}, Río Gallegos",
            'title' => "Alquileres en {$nombre}, Río Gallegos: {total} avisos",
            'description' => $barrio['meta_description']
                ?: "{total} avisos de alquiler en el barrio {$nombre} de Río Gallegos{desde}. Casas, departamentos y monoambientes, mensuales y temporarios.",
            'intro' => $barrio['intro']
                ?: "Avisos de alquiler en el barrio {$nombre} de Río Gallegos: casas, departamentos y monoambientes, por mes o por día. Todos los avisos se revisan periódicamente para confirmar que siguen disponibles.",
            'periodo' => 'mes',
        ];
    }

    /**
     * Completa {total} y {desde} con datos reales.
     *
     * @param array{total: int, desde: ?int} $stats
     */
    public static function completar(string $texto, array $stats, string $periodo): string
    {
        $desde = $stats['desde'] !== null
            ? ', desde $' . Catalogo::numero($stats['desde']) . ($periodo === 'dia' ? ' por día' : ' por mes')
            : '';
        return strtr($texto, ['{total}' => (string) $stats['total'], '{desde}' => $desde]);
    }
}
