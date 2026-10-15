<?php
/** @var string $titulo @var string $contenido */
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;

$logueado = Auth::estaLogueado();
$flash = $logueado ? Flash::sacar() : [];
$pendientes = $enCola = 0;
if ($logueado) {
    $pendientes = (int) App\Db::valor("SELECT COUNT(*) FROM avisos WHERE estado = 'pendiente' AND email_confirmado_en IS NOT NULL");
    $enCola = App\Frescura\ColaVerificacion::contar();
}
?>
<!doctype html>
<html lang="es-AR" class="sin-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>document.documentElement.classList.remove('sin-js')</script>
<meta name="robots" content="noindex,nofollow">
<title><?= e($titulo) ?> · Panel · <?= e(app_nombre()) ?></title>
<link rel="icon" href="<?= e(asset('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
</head>
<body class="admin"
  data-mapa-tiles="<?= e(App\Config::get('MAPA_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png')) ?>"
  data-mapa-atribucion="<?= e(App\Config::get('MAPA_ATRIBUCION', '&copy; OpenStreetMap')) ?>"
  data-mapa-lat="<?= e(App\Config::get('MAPA_CENTRO_LAT', '-51.6230')) ?>"
  data-mapa-lng="<?= e(App\Config::get('MAPA_CENTRO_LNG', '-69.2168')) ?>">
<header class="cabecera">
  <div class="contenedor cabecera-fila">
    <a class="logo" href="/admin"><span class="logo-marca" aria-hidden="true">⌂</span> Panel</a>
    <?php if ($logueado): ?>
    <form method="post" action="/admin/logout"><?= Csrf::campo() ?><button class="boton boton-chico boton-claro" type="submit">Salir</button></form>
    <?php endif ?>
  </div>
  <?php if ($logueado): ?>
  <nav class="contenedor nav-principal" aria-label="Panel">
    <a href="/admin/avisos/nuevo"><strong>+ Nuevo aviso</strong></a>
    <a href="/admin/avisos">Avisos</a>
    <a href="/admin/moderacion">Moderación<?= $pendientes ? " <span class=\"globo\">{$pendientes}</span>" : '' ?></a>
    <a href="/admin/verificacion">Verificación<?= $enCola ? " <span class=\"globo\">{$enCola}</span>" : '' ?></a>
    <a href="/admin/inmobiliarias">Inmobiliarias</a>
    <a href="/admin/barrios">Barrios</a>
    <a href="/" target="_blank">Ver sitio ↗</a>
  </nav>
  <?php endif ?>
</header>
<main class="contenedor admin-main">
  <?php foreach ($flash as [$tipo, $mensaje]): ?>
  <p class="alerta alerta-<?= $tipo === 'ok' ? 'ok' : 'error' ?>"><?= e($mensaje) ?></p>
  <?php endforeach ?>
  <?= $contenido ?>
</main>
<script src="<?= e(asset('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
