<?php

declare(strict_types=1);

namespace App\Mail;

final class Email
{
    public function __construct(
        public readonly string $para,
        public readonly string $asunto,
        public readonly string $texto,
        public readonly ?string $responderA = null,
        public readonly ?string $html = null,
    ) {
    }

    /**
     * HTML simple con un botón: el link va en el href, así ningún cliente de correo lo corta al
     * partir líneas largas (en texto plano, un link de 90+ caracteres puede llegar cortado).
     *
     * @param list<string> $parrafos texto plano (se escapa)
     */
    public static function htmlConBoton(array $parrafos, string $textoBoton, string $url, string $pie): string
    {
        return self::htmlConBotones($parrafos, [$textoBoton => $url], $pie);
    }

    /**
     * Igual que htmlConBoton, con varios botones (el primero es el principal).
     *
     * @param list<string> $parrafos texto plano (se escapa)
     * @param array<string, string> $botones texto => url
     */
    public static function htmlConBotones(array $parrafos, array $botones, string $pie): string
    {
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#1d2733;max-width:520px">';
        foreach ($parrafos as $parrafo) {
            $html .= '<p>' . nl2br($e($parrafo)) . '</p>';
        }
        $html .= '<p style="margin:24px 0">';
        $primero = true;
        foreach ($botones as $texto => $url) {
            $colores = $primero ? 'background:#127a3e;color:#ffffff;border:2px solid #127a3e' : 'background:#ffffff;color:#127a3e;border:2px solid #127a3e';
            $html .= '<a href="' . $e($url) . '" style="' . $colores . ';padding:10px 18px;border-radius:8px;text-decoration:none;'
                . 'font-weight:bold;display:inline-block;margin:0 8px 8px 0">' . $e((string) $texto) . '</a>';
            $primero = false;
        }
        // Con varios botones, el link de respaldo es la página que los muestra todos (sin elegir respuesta).
        $url = count($botones) > 1 ? (string) strtok((string) reset($botones), '?') : (string) reset($botones);
        $html .= '</p><p style="font-size:13px;color:#5a6675">Si ' . (count($botones) > 1 ? 'los botones no funcionan' : 'el botón no funciona')
            . ', copiá este link completo en el navegador:<br><span style="word-break:break-all">' . $e($url) . '</span></p>'
            . '<p style="font-size:13px;color:#5a6675">' . nl2br($e($pie)) . '</p></div>';
        return $html;
    }
}
