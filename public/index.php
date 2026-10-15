<?php

declare(strict_types=1);

// Con el servidor embebido de PHP (composer serve) los archivos estáticos se sirven tal cual.
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($archivo) && $archivo !== __FILE__) {
        return false;
    }
}

require __DIR__ . '/../src/bootstrap.php';

App\App::correr();
