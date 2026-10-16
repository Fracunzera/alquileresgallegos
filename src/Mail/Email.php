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
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#1d2733;max-width:520px">';
        foreach ($parrafos as $parrafo) {
            $html .= '<p>' . nl2br($e($parrafo)) . '</p>';
        }
        $html .= '<p style="margin:24px 0"><a href="' . $e($url) . '" style="background:#127a3e;color:#ffffff;padding:12px 20px;'
            . 'border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block">' . $e($textoBoton) . '</a></p>'
            . '<p style="font-size:13px;color:#5a6675">Si el botón no funciona, copiá este link completo en el navegador:<br>'
            . '<span style="word-break:break-all">' . $e($url) . '</span></p>'
            . '<p style="font-size:13px;color:#5a6675">' . nl2br($e($pie)) . '</p></div>';
        return $html;
    }
}
