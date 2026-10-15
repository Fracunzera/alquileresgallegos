<?php

declare(strict_types=1);

namespace App\Mail;

/**
 * Envío transaccional con la API de Brevo (https://developers.brevo.com/reference/sendtransacemail).
 * El remitente (MAIL_FROM) tiene que estar verificado en Brevo, idealmente con el dominio
 * autenticado (SPF + DKIM) para no caer en spam.
 */
final class BrevoMailer implements Mailer
{
    private const URL = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $remitente,
        private readonly string $nombreRemitente,
    ) {
    }

    public function enviar(Email $email): bool
    {
        $ch = curl_init(self::URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['api-key: ' . $this->apiKey, 'Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($this->payload($email), JSON_UNESCAPED_UNICODE),
        ]);
        $respuesta = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($codigo >= 200 && $codigo < 300) {
            return true;
        }
        error_log("Brevo: falló el envío a {$email->para} (HTTP {$codigo}) {$error} " . substr((string) $respuesta, 0, 300));
        return false;
    }

    /** @return array<string, mixed> */
    public function payload(Email $email): array
    {
        $payload = [
            'sender' => ['name' => $this->nombreRemitente, 'email' => $this->remitente],
            'to' => [['email' => $email->para]],
            'subject' => $email->asunto,
            'textContent' => $email->texto,
        ];
        if ($email->responderA !== null) {
            $payload['replyTo'] = ['email' => $email->responderA];
        }
        return $payload;
    }
}
