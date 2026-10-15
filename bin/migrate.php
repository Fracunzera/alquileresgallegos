<?php

declare(strict_types=1);

/*
 * Aplica las migraciones pendientes de database/migrations en orden.
 * Uso: php bin/migrate.php [--seed]
 *   --seed  además corre database/seeds/*.sql (idempotentes)
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Db;

$pdo = Db::pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(100) NOT NULL PRIMARY KEY,
    aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$aplicadas = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$archivos = glob(RAIZ . '/database/migrations/*.sql') ?: [];
sort($archivos);

$nuevas = 0;
foreach ($archivos as $archivo) {
    $version = basename($archivo, '.sql');
    if (in_array($version, $aplicadas, true)) {
        continue;
    }
    // MySQL hace commit implícito en DDL: si una migración falla a la mitad hay que revisarla a mano.
    foreach (Db::sentencias((string) file_get_contents($archivo)) as $sentencia) {
        $pdo->exec($sentencia);
    }
    Db::run('INSERT INTO schema_migrations (version) VALUES (?)', [$version]);
    echo "✔ {$version}\n";
    $nuevas++;
}
echo $nuevas === 0 ? "Sin migraciones pendientes.\n" : "{$nuevas} migración(es) aplicada(s).\n";

if (in_array('--seed', $argv, true)) {
    foreach (glob(RAIZ . '/database/seeds/*.sql') ?: [] as $archivo) {
        foreach (Db::sentencias((string) file_get_contents($archivo)) as $sentencia) {
            $pdo->exec($sentencia);
        }
        echo '✔ seed ' . basename($archivo) . "\n";
    }
}
