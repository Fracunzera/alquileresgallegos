<?php

declare(strict_types=1);

namespace App;

use App\Services\Captcha;

/**
 * Cabeceras de seguridad y validaciones de la petición.
 */
final class Seguridad
{
    /** Único script inline permitido (quita la clase "sin-js" antes de pintar). */
    public const SCRIPT_INLINE = "document.documentElement.classList.remove('sin-js')";

    /**
     * Paths que no se procesan: con "\" o empezando con "//" un redirect relativo se convierte en
     * uno a otro dominio (los navegadores leen "\" como "/").
     */
    public static function pathPeligroso(string $path): bool
    {
        return str_contains($path, '\\') || str_starts_with($path, '//') || str_contains($path, "\0");
    }

    public static function enviarCabeceras(): void
    {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Content-Security-Policy: ' . self::csp());
        if (self::sitioHttps()) {
            header('Strict-Transport-Security: max-age=15552000');
        }
        if (!self::indexable()) {
            header('X-Robots-Tag: noindex, nofollow');
        }
    }

    /** APP_INDEXAR=0 en el sitio de prueba: nada se indexa en Google (evita contenido duplicado con el dominio final). */
    public static function indexable(): bool
    {
        return Config::bool('APP_INDEXAR', true);
    }

    public static function csp(): string
    {
        $tiles = self::origenTiles((string) Config::get('MAPA_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'));
        $script = ["'self'", "'sha256-" . base64_encode(hash('sha256', self::SCRIPT_INLINE, true)) . "'"];
        $frame = ["'none'"];
        if (Captcha::activo()) {
            $script[] = Captcha::ORIGEN;
            $frame = [Captcha::ORIGEN];
        }
        return implode('; ', [
            "default-src 'self'",
            'script-src ' . implode(' ', $script),
            "style-src 'self' 'unsafe-inline'", // Leaflet posiciona el mapa con estilos inline
            "img-src 'self' data: " . $tiles,
            "connect-src 'self'",
            'frame-src ' . implode(' ', $frame),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }

    /** "https://{s}.tile.example.org/{z}/…" → "https://*.tile.example.org" */
    public static function origenTiles(string $url): string
    {
        $partes = parse_url(str_replace(['{s}', '{z}', '{x}', '{y}', '{r}'], ['sub', '0', '0', '0', ''], $url));
        if (!isset($partes['scheme'], $partes['host'])) {
            return '';
        }
        $host = str_contains($url, '{s}.') ? preg_replace('/^sub\./', '*.', $partes['host']) : $partes['host'];
        return $partes['scheme'] . '://' . $host;
    }

    public static function sitioHttps(): bool
    {
        return str_starts_with((string) Config::get('APP_URL', ''), 'https://');
    }
}
