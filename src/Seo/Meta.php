<?php

declare(strict_types=1);

namespace App\Seo;

/**
 * Metadatos de una página. El layout los vuelca en el <head>.
 */
final class Meta
{
    /** @param list<array<string, mixed>> $jsonLd */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $canonical = null,     // path absoluto del sitio ("/casas-alquiler-rio-gallegos")
        public bool $indexar = true,
        public ?string $prev = null,
        public ?string $next = null,
        public ?string $imagen = null,        // path de la imagen para Open Graph
        public string $ogTipo = 'website',
        public array $jsonLd = [],
        public ?int $imagenAncho = null,
        public ?int $imagenAlto = null,
    ) {
        $this->title = self::conMarca($title);
        $this->description = self::recortar($description, 158);
    }

    /** Agrega " | Alquileres Río Gallegos" solo si el título sigue entrando en lo que muestra Google. */
    public static function conMarca(string $title, int $max = 65): string
    {
        $sufijo = ' | ' . app_nombre();
        return mb_strlen($title . $sufijo) <= $max ? $title . $sufijo : $title;
    }

    /** Recorta en límite de palabra y agrega "…" si hace falta. */
    public static function recortar(string $texto, int $max): string
    {
        $texto = trim((string) preg_replace('/\s+/u', ' ', $texto));
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }
        $corte = mb_substr($texto, 0, $max - 1);
        $espacio = mb_strrpos($corte, ' ');
        return rtrim($espacio !== false ? mb_substr($corte, 0, $espacio) : $corte, " ,.;:-") . '…';
    }
}
