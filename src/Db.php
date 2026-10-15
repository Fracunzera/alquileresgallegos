<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                Config::get('DB_HOST', '127.0.0.1'),
                Config::int('DB_PORT', 3306),
                Config::get('DB_NAME', 'alquileres')
            );
            self::$pdo = new PDO($dsn, Config::get('DB_USER', 'root'), Config::get('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '-03:00'");
        }
        return self::$pdo;
    }

    /** @param array<string|int, mixed> $params */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** @param array<string|int, mixed> $params @return array<string, mixed>|null */
    public static function fila(string $sql, array $params = []): ?array
    {
        $fila = self::run($sql, $params)->fetch();
        return $fila === false ? null : $fila;
    }

    /** @param array<string|int, mixed> $params @return list<array<string, mixed>> */
    public static function filas(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param array<string|int, mixed> $params */
    public static function valor(string $sql, array $params = []): mixed
    {
        $valor = self::run($sql, $params)->fetchColumn();
        return $valor === false ? null : $valor;
    }

    /**
     * Divide un archivo SQL en sentencias (las migraciones no usan ';' dentro de strings
     * al final de una línea, que es lo único que este separador no soporta).
     *
     * @return list<string>
     */
    public static function sentencias(string $sql): array
    {
        $sinComentarios = preg_replace('/^\s*--.*$/m', '', $sql);
        $partes = preg_split('/;\s*(?:\R|$)/', (string) $sinComentarios);
        return array_values(array_filter(array_map('trim', $partes), fn ($s) => $s !== ''));
    }
}
