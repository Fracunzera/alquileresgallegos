<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use App\Http;

/**
 * Captcha opcional con Cloudflare Turnstile (gratis y casi siempre invisible).
 * Apagado mientras TURNSTILE_SITE_KEY / TURNSTILE_SECRET estén vacíos: el sitio ya tiene campo trampa,
 * token con tiempo mínimo, límites por IP, confirmación por email y moderación. Se prende si aparece spam.
 */
final class Captcha
{
    public const ORIGEN = 'https://challenges.cloudflare.com';
    private const VERIFICAR = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function activo(): bool
    {
        return (string) Config::get('TURNSTILE_SITE_KEY', '') !== '' && (string) Config::get('TURNSTILE_SECRET', '') !== '';
    }

    /** HTML del widget (vacío si está apagado). */
    public static function widget(): string
    {
        if (!self::activo()) {
            return '';
        }
        return '<div class="cf-turnstile" data-sitekey="' . e((string) Config::get('TURNSTILE_SITE_KEY')) . '" data-language="es"></div>'
            . '<script src="' . self::ORIGEN . '/turnstile/v0/api.js" async defer></script>';
    }

    /**
     * true si está apagado o si Cloudflare valida la respuesta. Sin respuesta del navegador se rechaza;
     * si Cloudflare no contesta se deja pasar (quedan las otras defensas) y se registra en el log.
     */
    public static function valido(): bool
    {
        if (!self::activo()) {
            return true;
        }
        $respuesta = (string) Http::post('cf-turnstile-response');
        if ($respuesta === '') {
            return false;
        }
        $ch = curl_init(self::VERIFICAR);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => Config::get('TURNSTILE_SECRET'),
                'response' => $respuesta,
                'remoteip' => Http::ip(),
            ]),
        ]);
        $cuerpo = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($cuerpo === false || $codigo !== 200) {
            error_log("Turnstile: sin respuesta de Cloudflare (HTTP {$codigo}), se acepta el envío");
            return true;
        }
        return (bool) (json_decode((string) $cuerpo, true)['success'] ?? false);
    }
}
