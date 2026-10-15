<?php

declare(strict_types=1);

namespace App;

/** Se lanza desde cualquier controlador para responder 404. */
final class NoEncontrado extends \RuntimeException
{
}
