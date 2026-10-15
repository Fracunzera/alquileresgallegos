<?php

declare(strict_types=1);

/*
 * Carga avisos de DEMOSTRACIÓN para desarrollo (fotos generadas, inmobiliarias ficticias).
 * Se niega a correr con APP_ENV=production.
 * Uso: php bin/datos-demo.php [cantidad] [--limpiar]
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Config;
use App\Db;
use App\Services\Fotos;
use App\Services\Slugger;

if (Config::get('APP_ENV') === 'production') {
    fwrite(STDERR, "No se cargan datos de demo en producción.\n");
    exit(1);
}

if (in_array('--limpiar', $argv, true)) {
    foreach (Db::filas('SELECT id FROM avisos') as $a) {
        Fotos::borrarDeAviso((int) $a['id']);
    }
    Db::run('DELETE FROM avisos');
    Db::run("DELETE FROM inmobiliarias WHERE slug LIKE 'demo-%'");
    echo "Avisos de demo borrados.\n";
}

$cantidad = (int) ($argv[1] ?? 40) ?: 40;
mt_srand(2966);

$inmos = [];
foreach ([['Inmobiliaria Demo Austral', 'demo-austral'], ['Propiedades Demo Sur', 'demo-sur']] as [$nombre, $slug]) {
    Db::run(
        'INSERT IGNORE INTO inmobiliarias (nombre, slug, telefono, whatsapp, direccion, descripcion) VALUES (?, ?, ?, ?, ?, ?)',
        [$nombre, $slug, '(02966) 420000', '5492966000000', 'Av. Roca 1000', "{$nombre} es una inmobiliaria ficticia para pruebas."]
    );
    $inmos[] = (int) Db::valor('SELECT id FROM inmobiliarias WHERE slug = ?', [$slug]);
}
$barrios = array_map('intval', array_column(Db::filas('SELECT id FROM barrios WHERE activo = 1'), 'id'));
$barriosPopulares = array_slice($barrios, 0, 8);

$plantillas = [
    'departamento' => ['Departamento de {d} dormitorio{s} {extra}', [1, 3]],
    'casa' => ['Casa de {d} dormitorio{s} {extra}', [2, 4]],
    'monoambiente' => ['Monoambiente {extra}', [0, 0]],
    'ph' => ['PH de {d} dormitorio{s} {extra}', [1, 2]],
    'local' => ['Local comercial {extra}', [0, 0]],
    'habitacion' => ['Habitación en casa compartida {extra}', [1, 1]],
];
$extras = ['con cochera', 'luminoso', 'a estrenar', 'con patio', 'amueblado', 'cerca del centro', 'con calefacción central', 'con quincho', 'reciclado', 'con vista a la ría'];
$parrafos = [
    'Ubicado en una zona tranquila, a pocas cuadras de comercios, colegios y paradas de colectivo.',
    'Cuenta con calefacción por tiro balanceado en todos los ambientes y agua caliente con termotanque.',
    'Cocina separada con mesada de granito y alacena. Baño completo con ducha.',
    'Se pide garantía propietaria o seguro de caución, recibo de sueldo y mes de depósito.',
    'Ideal para personas que vienen a trabajar a la ciudad. Incluye ropa de cama, toallas y wifi.',
    'Ingreso independiente, ventanas de doble vidrio y pisos de cerámica. No se aceptan mascotas.',
    'Los servicios de luz y gas corren por cuenta del inquilino. Disponible desde el mes próximo.',
];

$categorias = ['departamento', 'departamento', 'departamento', 'casa', 'casa', 'monoambiente', 'monoambiente', 'ph', 'local', 'habitacion'];
for ($n = 0; $n < $cantidad; $n++) {
    $categoria = $categorias[mt_rand(0, count($categorias) - 1)];
    $tipo = $categoria !== 'local' && mt_rand(1, 100) <= 30 ? 'temporario' : 'mensual';
    [$plantilla, [$dMin, $dMax]] = $plantillas[$categoria];
    $dorm = mt_rand($dMin, $dMax);
    $titulo = ucfirst(trim(strtr($plantilla, ['{d}' => $dorm, '{s}' => $dorm === 1 ? '' : 's', '{extra}' => $extras[mt_rand(0, count($extras) - 1)]])));
    if ($tipo === 'temporario') {
        $titulo .= ' por día';
    }
    $descripcion = implode("\n\n", array_map(fn ($i) => $parrafos[$i], (array) array_rand($parrafos, 3)));
    $esInmo = mt_rand(1, 100) <= 40;
    $precio = $tipo === 'temporario'
        ? mt_rand(30, 90) * 1000
        : ($categoria === 'monoambiente' ? mt_rand(28, 45) * 10000 : mt_rand(45, 120) * 10000);
    $barrio = mt_rand(1, 100) <= 75 ? $barriosPopulares[mt_rand(0, count($barriosPopulares) - 1)] : $barrios[mt_rand(0, count($barrios) - 1)];
    $conMapa = mt_rand(1, 100) <= 60;
    $publicado = date('Y-m-d H:i:s', strtotime('-' . mt_rand(0, 40) . ' days -' . mt_rand(0, 23) . ' hours'));

    Db::run(
        'INSERT INTO avisos (titulo, slug, tipo, categoria, ambientes, dormitorios, banos, barrio_id, direccion, mostrar_direccion,
            lat, lng, precio, moneda, periodo, expensas, descripcion, contacto_whatsapp, contacto_telefono, contacto_email,
            publicador, inmobiliaria_id, destacado, estado, origen, ultima_verificacion, publicado_en, creado_en)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'ARS\', ?, ?, ?, ?, ?, ?, ?, ?, ?, \'activo\', \'admin\', ?, ?, ?)',
        [
            $titulo, Slugger::slug($titulo), $tipo, $categoria,
            $categoria === 'local' ? null : max(1, $dorm + 1), $categoria === 'local' ? null : $dorm, 1,
            $barrio, 'Calle Demo ' . mt_rand(100, 2000), mt_rand(0, 1),
            $conMapa ? -51.6230 + mt_rand(-150, 150) / 10000 : null, $conMapa ? -69.2168 + mt_rand(-250, 250) / 10000 : null,
            mt_rand(1, 100) <= 8 ? null : $precio, $tipo === 'temporario' ? 'dia' : 'mes',
            $tipo === 'mensual' && in_array($categoria, ['departamento', 'monoambiente'], true) ? mt_rand(3, 9) * 10000 : (mt_rand(0, 1) ? 0 : null),
            $descripcion, '549296600' . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT), null, mt_rand(0, 1) ? 'demo@example.com' : null,
            $esInmo ? 'inmobiliaria' : 'particular', $esInmo ? $inmos[mt_rand(0, 1)] : null, (int) (mt_rand(1, 100) <= 10),
            date('Y-m-d H:i:s', strtotime('-' . mt_rand(0, 30) . ' days')), $publicado, $publicado,
        ]
    );
    $id = (int) Db::pdo()->lastInsertId();
    $opciones = [null, 'si', 'no', 'consultar'];
    Db::run('UPDATE avisos SET acepta_mascotas = ?, acepta_chicos = ? WHERE id = ?', [$opciones[mt_rand(0, 3)], $opciones[mt_rand(0, 3)], $id]);

    for ($f = 0, $total = mt_rand(0, 5); $f < $total; $f++) {
        $tmp = tempnam(sys_get_temp_dir(), 'demo');
        $img = imagecreatetruecolor(1600, 1200);
        $color = imagecolorallocate($img, mt_rand(60, 200), mt_rand(80, 200), mt_rand(100, 220));
        imagefill($img, 0, 0, $color);
        imagestring($img, 5, 40, 40, "Foto demo {$id}-" . ($f + 1), imagecolorallocate($img, 255, 255, 255));
        imagejpeg($img, $tmp, 85);
        imagedestroy($img);
        Fotos::agregar($id, $tmp);
        unlink($tmp);
    }
}
echo "{$cantidad} avisos de demo cargados.\n";
