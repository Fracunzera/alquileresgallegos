<?php

declare(strict_types=1);

namespace App\Mail;

interface Mailer
{
    /** @return bool true si el proveedor aceptó el envío */
    public function enviar(Email $email): bool;
}
