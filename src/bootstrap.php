<?php

declare(strict_types=1);

/*
 * Arranque común para la web y los scripts de consola.
 * Autoload PSR-4 propio: el sitio corre en hosting compartido sin Composer
 * (Composer solo se usa para PHPUnit).
 */

define('RAIZ', dirname(__DIR__));

spl_autoload_register(static function (string $clase): void {
    if (str_starts_with($clase, 'App\\')) {
        $archivo = RAIZ . '/src/' . str_replace('\\', '/', substr($clase, 4)) . '.php';
        if (is_file($archivo)) {
            require $archivo;
        }
    }
});

App\Config::cargar(RAIZ . '/.env');

date_default_timezone_set('America/Argentina/Rio_Gallegos');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', App\Config::bool('APP_DEBUG') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', RAIZ . '/storage/logs/php-error.log');

require RAIZ . '/src/helpers.php';
