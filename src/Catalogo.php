<?php

declare(strict_types=1);

namespace App;

/**
 * Valores cerrados del dominio y cómo se muestran.
 */
final class Catalogo
{
    public const TIPOS = [
        'mensual' => 'Alquiler mensual',
        'temporario' => 'Alquiler temporario',
    ];

    public const CATEGORIAS = [
        'departamento' => 'Departamento',
        'casa' => 'Casa',
        'monoambiente' => 'Monoambiente',
        'ph' => 'PH',
        'local' => 'Local',
        'habitacion' => 'Habitación',
    ];

    /** Plural en minúscula para armar frases ("casas en alquiler…"). */
    public const CATEGORIAS_PLURAL = [
        'departamento' => 'departamentos',
        'casa' => 'casas',
        'monoambiente' => 'monoambientes',
        'ph' => 'PH',
        'local' => 'locales',
        'habitacion' => 'habitaciones',
    ];

    public const ESTADOS = [
        'pendiente' => 'Pendiente de moderación',
        'activo' => 'Activo',
        'pausado' => 'Pausado',
        'alquilado' => 'Alquilado',
        'rechazado' => 'Rechazado',
    ];

    public const MONEDAS = ['ARS' => '$', 'USD' => 'US$'];
    public const PERIODOS = ['mes' => 'por mes', 'dia' => 'por día'];

    public static function numero(int $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    /** "$650.000 por mes", "US$ 40 por día" o "Consultar precio". @param array<string, mixed> $aviso */
    public static function precio(array $aviso, bool $conPeriodo = true): string
    {
        if ($aviso['precio'] === null) {
            return 'Consultar precio';
        }
        $simbolo = self::MONEDAS[$aviso['moneda']] ?? '$';
        $texto = $simbolo . ($aviso['moneda'] === 'USD' ? ' ' : '') . self::numero((int) $aviso['precio']);
        return $conPeriodo ? $texto . ' ' . (self::PERIODOS[$aviso['periodo']] ?? '') : $texto;
    }

    /** @param array<string, mixed> $aviso */
    public static function expensas(array $aviso): ?string
    {
        if ($aviso['expensas'] === null) {
            return null;
        }
        return (int) $aviso['expensas'] === 0 ? 'Sin expensas' : '+ $' . self::numero((int) $aviso['expensas']) . ' de expensas';
    }

    /** "2 amb · 1 dorm · 1 baño". @param array<string, mixed> $aviso */
    public static function ambientes(array $aviso): string
    {
        $partes = [];
        if (!empty($aviso['ambientes'])) {
            $partes[] = $aviso['ambientes'] . ' amb.';
        }
        if (!empty($aviso['dormitorios'])) {
            $partes[] = $aviso['dormitorios'] . ' dorm.';
        }
        if (!empty($aviso['banos'])) {
            $partes[] = $aviso['banos'] . ((int) $aviso['banos'] === 1 ? ' baño' : ' baños');
        }
        return implode(' · ', $partes);
    }

    /** "Departamento en alquiler mensual en Centro". @param array<string, mixed> $aviso */
    public static function descripcionCorta(array $aviso): string
    {
        $tipo = $aviso['tipo'] === 'temporario' ? 'alquiler temporario' : 'alquiler mensual';
        $texto = (self::CATEGORIAS[$aviso['categoria']] ?? 'Propiedad') . ' en ' . $tipo;
        if (!empty($aviso['barrio_nombre'])) {
            $texto .= ' en ' . $aviso['barrio_nombre'];
        }
        return $texto;
    }

    /** "hoy", "hace 3 días", "hace 2 meses". */
    public static function haceCuanto(?string $fecha): ?string
    {
        if ($fecha === null) {
            return null;
        }
        $dias = (int) (new \DateTimeImmutable('today'))->diff(new \DateTimeImmutable(substr($fecha, 0, 10)))->days;
        return match (true) {
            $dias === 0 => 'hoy',
            $dias === 1 => 'ayer',
            $dias < 30 => "hace {$dias} días",
            $dias < 60 => 'hace 1 mes',
            default => 'hace ' . intdiv($dias, 30) . ' meses',
        };
    }

    public static function urlAviso(int $id, string $slug): string
    {
        return '/aviso/' . $id . '-' . $slug;
    }

    public static function urlBarrio(string $slug): string
    {
        return '/alquileres-' . $slug . '-rio-gallegos';
    }

    public static function urlInmobiliaria(string $slug): string
    {
        return '/inmobiliaria/' . $slug;
    }

    /** URL pública de una foto procesada en un ancho (400 o 1200). */
    public static function urlFoto(string $archivo, int $ancho): string
    {
        return '/uploads/' . $archivo . '-' . $ancho . '.webp';
    }
}
