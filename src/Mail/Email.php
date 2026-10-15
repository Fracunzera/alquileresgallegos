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
    ) {
    }
}
