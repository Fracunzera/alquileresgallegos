<?php

declare(strict_types=1);

namespace App\Services;

use App\Db;

/**
 * Procesa fotos subidas: valida, corrige la rotación EXIF, genera WebP en 1200 y 400 px
 * y descarta el original (y con él los metadatos, incluida la ubicación GPS del celular).
 */
final class Fotos
{
    public const ANCHOS = [1200, 400];
    private const MAX_PIXELES = 40_000_000;
    private const TIPOS = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    public static function directorioBase(): string
    {
        return RAIZ . '/public/uploads';
    }

    /**
     * Procesa un archivo de imagen y lo agrega al aviso al final del orden.
     *
     * @throws \RuntimeException con un mensaje para mostrar al usuario
     */
    public static function agregar(int $avisoId, string $rutaTemporal): int
    {
        $archivo = 'avisos/' . $avisoId . '/' . bin2hex(random_bytes(8));
        [$ancho, $alto] = self::procesar($rutaTemporal, self::directorioBase() . '/' . $archivo);
        $orden = (int) Db::valor('SELECT COALESCE(MAX(orden), -1) + 1 FROM aviso_fotos WHERE aviso_id = ?', [$avisoId]);
        Db::run(
            'INSERT INTO aviso_fotos (aviso_id, archivo, ancho, alto, orden) VALUES (?, ?, ?, ?, ?)',
            [$avisoId, $archivo, $ancho, $alto, $orden]
        );
        return (int) Db::pdo()->lastInsertId();
    }

    /**
     * Agrega las fotos de un input <input type="file" name="x[]" multiple>.
     *
     * @param array<string, mixed> $files entrada de $_FILES
     * @return list<string> errores (uno por foto rechazada)
     */
    public static function agregarSubidas(int $avisoId, array $files, int $maximo): array
    {
        $errores = [];
        $actuales = (int) Db::valor('SELECT COUNT(*) FROM aviso_fotos WHERE aviso_id = ?', [$avisoId]);
        $maxBytes = \App\Config::int('FOTOS_MAX_MB', 8) * 1024 * 1024;
        foreach ((array) ($files['tmp_name'] ?? []) as $i => $tmp) {
            $nombre = (string) ($files['name'][$i] ?? 'foto');
            $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($actuales >= $maximo) {
                $errores[] = "Se pueden subir hasta {$maximo} fotos por aviso.";
                break;
            }
            if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp) || filesize($tmp) > $maxBytes) {
                $errores[] = "«{$nombre}» no se pudo subir (¿pesa más de " . ($maxBytes >> 20) . ' MB?).';
                continue;
            }
            try {
                self::agregar($avisoId, $tmp);
                $actuales++;
            } catch (\RuntimeException $e) {
                $errores[] = "«{$nombre}»: " . $e->getMessage();
            }
        }
        return $errores;
    }

    /** @return array{0: int, 1: int} ancho y alto de la versión grande */
    public static function procesar(string $origen, string $destinoBase): array
    {
        $info = @getimagesize($origen);
        if ($info === false || !in_array($info[2], self::TIPOS, true)) {
            throw new \RuntimeException('no es una imagen JPG, PNG o WebP.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELES) {
            throw new \RuntimeException('la imagen es demasiado grande.');
        }
        ini_set('memory_limit', '512M');

        $imagen = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($origen),
            IMAGETYPE_PNG => @imagecreatefrompng($origen),
            IMAGETYPE_WEBP => @imagecreatefromwebp($origen),
        };
        if ($imagen === false) {
            throw new \RuntimeException('la imagen está dañada.');
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            $imagen = self::corregirOrientacion($imagen, $origen);
        }

        $dir = dirname($destinoBase);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('no se pudo guardar la imagen.');
        }

        $resultado = [0, 0];
        foreach (self::ANCHOS as $ancho) {
            $copia = imagesx($imagen) > $ancho ? imagescale($imagen, $ancho, -1, IMG_BICUBIC) : $imagen;
            if ($copia === false) {
                throw new \RuntimeException('no se pudo procesar la imagen.');
            }
            imagewebp($copia, "{$destinoBase}-{$ancho}.webp", 80);
            if ($ancho === self::ANCHOS[0]) {
                $resultado = [imagesx($copia), imagesy($copia)];
            }
            if ($copia !== $imagen) {
                imagedestroy($copia);
            }
        }
        imagedestroy($imagen);
        return $resultado;
    }

    public static function borrar(int $fotoId): void
    {
        $foto = Db::fila('SELECT * FROM aviso_fotos WHERE id = ?', [$fotoId]);
        if ($foto === null) {
            return;
        }
        foreach (self::ANCHOS as $ancho) {
            @unlink(self::directorioBase() . '/' . $foto['archivo'] . "-{$ancho}.webp");
        }
        Db::run('DELETE FROM aviso_fotos WHERE id = ?', [$fotoId]);
    }

    public static function borrarDeAviso(int $avisoId): void
    {
        foreach (Db::filas('SELECT id FROM aviso_fotos WHERE aviso_id = ?', [$avisoId]) as $foto) {
            self::borrar((int) $foto['id']);
        }
        @rmdir(self::directorioBase() . '/avisos/' . $avisoId);
    }

    private static function corregirOrientacion(\GdImage $imagen, string $origen): \GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($origen) : false;
        $rotacion = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($rotacion === 0) {
            return $imagen;
        }
        $rotada = imagerotate($imagen, $rotacion, 0);
        if ($rotada === false) {
            return $imagen;
        }
        imagedestroy($imagen);
        return $rotada;
    }
}
