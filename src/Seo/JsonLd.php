<?php

declare(strict_types=1);

namespace App\Seo;

use App\Catalogo;

/**
 * Datos estructurados schema.org. Se vuelcan en el <head> como application/ld+json.
 */
final class JsonLd
{
    /** Tipo de schema.org para la propiedad según la categoría del aviso. */
    private const TIPOS = [
        'departamento' => 'Apartment',
        'monoambiente' => 'Apartment',
        'casa' => 'SingleFamilyResidence',
        'ph' => 'House',
        'habitacion' => 'Room',
        'local' => 'Place',
    ];

    /**
     * Ficha: RealEstateListing con la propiedad (Residence) y la oferta de alquiler (Offer).
     *
     * @param array<string, mixed> $aviso
     * @param list<array<string, mixed>> $fotos
     * @param array{lat: float, lng: float, aproximado: bool}|null $mapa coordenadas ya redondeadas si corresponde
     * @return array<string, mixed>
     */
    public static function aviso(array $aviso, array $fotos, ?array $mapa): array
    {
        $url = url_abs(Catalogo::urlAviso((int) $aviso['id'], $aviso['slug']));

        $direccion = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $aviso['mostrar_direccion'] && $aviso['direccion'] ? $aviso['direccion'] : null,
            'addressLocality' => 'Río Gallegos',
            'addressRegion' => 'Santa Cruz',
            'addressCountry' => 'AR',
        ]);
        $propiedad = array_filter([
            '@type' => self::TIPOS[$aviso['categoria']] ?? 'Residence',
            'name' => Catalogo::descripcionCorta($aviso) . ', Río Gallegos',
            'numberOfRooms' => $aviso['ambientes'] !== null ? (int) $aviso['ambientes'] : null,
            'numberOfBedrooms' => $aviso['dormitorios'] !== null ? (int) $aviso['dormitorios'] : null,
            'numberOfBathroomsTotal' => $aviso['banos'] !== null ? (int) $aviso['banos'] : null,
            'address' => $direccion,
            'containedInPlace' => $aviso['barrio_nombre'] ? ['@type' => 'Place', 'name' => $aviso['barrio_nombre'] . ', Río Gallegos'] : null,
            'geo' => $mapa ? ['@type' => 'GeoCoordinates', 'latitude' => $mapa['lat'], 'longitude' => $mapa['lng']] : null,
        ], fn ($v) => $v !== null);

        $oferta = [
            '@type' => 'Offer',
            'businessFunction' => 'http://purl.org/goodrelations/v1#LeaseOut',
            'availability' => $aviso['estado'] === 'activo' ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
            'url' => $url,
        ];
        if ($aviso['precio'] !== null) {
            $oferta['price'] = (int) $aviso['precio'];
            $oferta['priceCurrency'] = $aviso['moneda'];
            $especificaciones = [[
                '@type' => 'UnitPriceSpecification',
                'name' => 'Alquiler',
                'price' => (int) $aviso['precio'],
                'priceCurrency' => $aviso['moneda'],
                'unitCode' => $aviso['periodo'] === 'dia' ? 'DAY' : 'MON',
            ]];
            if ($aviso['expensas'] !== null && (int) $aviso['expensas'] > 0) {
                $especificaciones[] = [
                    '@type' => 'UnitPriceSpecification',
                    'name' => 'Expensas',
                    'price' => (int) $aviso['expensas'],
                    'priceCurrency' => 'ARS',
                    'unitCode' => 'MON',
                ];
            }
            $oferta['priceSpecification'] = $especificaciones;
        }
        $oferta['offeredBy'] = $aviso['inmobiliaria_slug']
            ? ['@type' => 'RealEstateAgent', 'name' => $aviso['inmobiliaria_nombre'], 'url' => url_abs(Catalogo::urlInmobiliaria($aviso['inmobiliaria_slug']))]
            : ['@type' => 'Person', 'name' => 'Dueño directo'];

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            '@id' => $url . '#aviso',
            'url' => $url,
            'name' => $aviso['titulo'],
            'description' => mb_substr(trim((string) preg_replace('/\s+/u', ' ', $aviso['descripcion'])), 0, 500),
            'datePosted' => self::fecha($aviso['publicado_en'] ?? $aviso['creado_en']),
            'dateModified' => self::fecha($aviso['actualizado_en']),
            'image' => array_map(fn ($f) => url_abs(Catalogo::urlFoto($f['archivo'], 1200)), $fotos) ?: null,
            'about' => $propiedad,
            'offers' => $oferta,
        ], fn ($v) => $v !== null);
    }

    /**
     * @param list<array{0: string, 1: ?string}> $migas
     * @return array<string, mixed>
     */
    public static function migas(array $migas, string $urlActual): array
    {
        $items = [];
        foreach ($migas as $i => [$nombre, $href]) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $nombre, 'item' => url_abs($href ?? $urlActual)];
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /**
     * Listado (landing, barrio, inmobiliaria): ItemList con las URLs de los avisos de la página.
     *
     * @param list<array<string, mixed>> $avisos
     * @return array<string, mixed>
     */
    public static function listado(string $nombre, array $avisos, int $offset): array
    {
        $items = [];
        foreach ($avisos as $i => $aviso) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $offset + $i + 1,
                'url' => url_abs(Catalogo::urlAviso((int) $aviso['id'], $aviso['slug'])),
                'name' => $aviso['titulo'],
            ];
        }
        return ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $nombre, 'numberOfItems' => count($items), 'itemListElement' => $items];
    }

    /** @return array<string, mixed> */
    public static function sitio(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => app_nombre(),
            'url' => url_abs('/'),
            'inLanguage' => 'es-AR',
            'description' => 'Portal de avisos de alquileres mensuales y temporarios de Río Gallegos, Santa Cruz.',
        ];
    }

    /** @param array<string, mixed> $inmo @return array<string, mixed> */
    public static function inmobiliaria(array $inmo): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateAgent',
            'name' => $inmo['nombre'],
            'url' => url_abs(Catalogo::urlInmobiliaria($inmo['slug'])),
            'logo' => $inmo['logo'] ? url_abs($inmo['logo']) : null,
            'image' => $inmo['logo'] ? url_abs($inmo['logo']) : null,
            'telephone' => $inmo['telefono'] ?: ($inmo['whatsapp'] ? '+' . $inmo['whatsapp'] : null),
            'email' => $inmo['email'] ?: null,
            'sameAs' => $inmo['web'] ? [$inmo['web']] : null,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $inmo['direccion'] ?: null,
                'addressLocality' => 'Río Gallegos',
                'addressRegion' => 'Santa Cruz',
                'addressCountry' => 'AR',
            ]),
            'areaServed' => 'Río Gallegos',
        ], fn ($v) => $v !== null);
    }

    private static function fecha(?string $fecha): ?string
    {
        return $fecha ? (new \DateTimeImmutable($fecha))->format(DATE_ATOM) : null;
    }
}
