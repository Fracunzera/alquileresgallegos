<?php

declare(strict_types=1);

/*
 * Crea (o actualiza la contraseña de) un usuario del panel.
 * Uso: php bin/crear-admin.php email@dominio.com "Nombre"
 * La contraseña se pide por consola (o ADMIN_PASSWORD=... para scripts).
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Db;

[$_, $email, $nombre] = $argv + [null, null, null];
$email = mb_strtolower(trim((string) $email));
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Uso: php bin/crear-admin.php email@dominio.com \"Nombre\"\n");
    exit(1);
}

$password = getenv('ADMIN_PASSWORD') ?: null;
if ($password === null) {
    echo 'Contraseña (mínimo 10 caracteres): ';
    system('stty -echo 2>/dev/null');
    $password = trim((string) fgets(STDIN));
    system('stty echo 2>/dev/null');
    echo "\n";
}
if (mb_strlen($password) < 10) {
    fwrite(STDERR, "La contraseña debe tener al menos 10 caracteres.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
Db::run(
    'INSERT INTO admins (email, nombre, password_hash) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE password_hash = ?, nombre = COALESCE(?, nombre)',
    [$email, $nombre ?: $email, $hash, $hash, $nombre ?: null]
);
echo "Admin {$email} listo.\n";
