<?php

declare(strict_types=1);

namespace Tests;

use App\Config;
use App\Mail\BrevoMailer;
use App\Mail\Email;
use App\Services\FormToken;
use PHPUnit\Framework\TestCase;

final class MailTest extends TestCase
{
    public function testPayloadDeBrevo(): void
    {
        $mailer = new BrevoMailer('clave', 'avisos@ejemplo.com.ar', 'Alquileres Río Gallegos');
        $payload = $mailer->payload(new Email('ana@example.com', 'Asunto ñ', "Hola\nchau", 'responder@example.com'));

        $this->assertSame(['name' => 'Alquileres Río Gallegos', 'email' => 'avisos@ejemplo.com.ar'], $payload['sender']);
        $this->assertSame([['email' => 'ana@example.com']], $payload['to']);
        $this->assertSame('Asunto ñ', $payload['subject']);
        $this->assertSame("Hola\nchau", $payload['textContent']);
        $this->assertSame(['email' => 'responder@example.com'], $payload['replyTo']);
    }

    public function testFormTokenPorPropositoYTiempo(): void
    {
        Config::set('APP_KEY', str_repeat('k', 64));
        $hace10 = FormToken::generar('publicar', time() - 10);

        $this->assertTrue(FormToken::valido($hace10, 'publicar', 5, 3600));
        $this->assertFalse(FormToken::valido($hace10, 'denunciar', 5, 3600), 'un token no sirve para otro formulario');
        $this->assertFalse(FormToken::valido(FormToken::generar('publicar'), 'publicar', 5, 3600), 'enviado demasiado rápido');
        $this->assertFalse(FormToken::valido(FormToken::generar('publicar', time() - 7200), 'publicar', 5, 3600), 'vencido');
        $this->assertFalse(FormToken::valido('123.firmafalsa', 'publicar', 0, 3600));
    }

    public function testHtmlConBotonEscapaYLlevaElLinkEnElHref(): void
    {
        $url = 'https://alquiler.blucaro.site/cuenta/entrar/' . str_repeat('a', 32);
        $html = Email::htmlConBoton(['Hola <Ana>'], 'Entrar', $url, 'Pie');
        $this->assertStringContainsString('href="' . $url . '"', $html);
        $this->assertStringContainsString('Hola &lt;Ana&gt;', $html);
        $this->assertStringNotContainsString('<Ana>', $html);

        $payload = (new BrevoMailer('k', 'a@b.c', 'X'))->payload(new Email('d@e.f', 'Asunto', 'texto', null, $html));
        $this->assertSame($html, $payload['htmlContent']);
        $this->assertSame('texto', $payload['textContent']);
    }

    public function testHtmlConBotonesMuestraTodosYElLinkDeRespaldoSinRespuesta(): void
    {
        $link = 'https://alquiler.blucaro.site/verificar/7/' . str_repeat('b', 48);
        $html = Email::htmlConBotones(['¿Sigue disponible?'], ['Sigue disponible' => $link . '?r=disponible', 'Ya lo alquilé' => $link . '?r=alquilado'], 'Pie');
        $this->assertStringContainsString('href="' . $link . '?r=disponible"', $html);
        $this->assertStringContainsString('href="' . $link . '?r=alquilado"', $html);
        $this->assertStringContainsString('los botones no funcionan', $html);
        $this->assertStringContainsString('break-all">' . $link . '</span>', $html);
    }
}
