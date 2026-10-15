<?php
/** @var App\Seo\Meta $meta @var string $contenido */
use App\Catalogo;
use App\Seo\Landings;

$barriosFooter = [];
try {
    $barriosFooter = (new App\Repositories\BarrioRepo())->conAvisos();
} catch (Throwable) {
}
$ogImagen = url_abs($meta->imagen ?? '/assets/img/og-default.png');
?>
<!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($meta->title) ?></title>
<meta name="description" content="<?= e($meta->description) ?>">
<?php if ($meta->canonical !== null): ?>
<link rel="canonical" href="<?= e(url_abs($meta->canonical)) ?>">
<?php endif ?>
<?php if (!$meta->indexar): ?>
<meta name="robots" content="noindex,follow">
<?php endif ?>
<?php if ($meta->prev !== null): ?>
<link rel="prev" href="<?= e(url_abs($meta->prev)) ?>">
<?php endif ?>
<?php if ($meta->next !== null): ?>
<link rel="next" href="<?= e(url_abs($meta->next)) ?>">
<?php endif ?>
<meta property="og:site_name" content="<?= e(app_nombre()) ?>">
<meta property="og:locale" content="es_AR">
<meta property="og:type" content="<?= e($meta->ogTipo) ?>">
<meta property="og:title" content="<?= e($meta->title) ?>">
<meta property="og:description" content="<?= e($meta->description) ?>">
<?php if ($meta->canonical !== null): ?>
<meta property="og:url" content="<?= e(url_abs($meta->canonical)) ?>">
<?php endif ?>
<meta property="og:image" content="<?= e($ogImagen) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0d3b66">
<link rel="icon" href="<?= e(asset('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
<?php foreach ($meta->jsonLd as $ld): ?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endforeach ?>
</head>
<body<?= !empty($conBarraContacto) ? ' class="con-barra-contacto"' : '' ?>>
<header class="cabecera">
  <div class="contenedor cabecera-fila">
    <a class="logo" href="/"><span class="logo-marca" aria-hidden="true">⌂</span> Alquileres <strong>Río Gallegos</strong></a>
    <a class="boton boton-chico boton-claro" href="/publicar">Publicar gratis</a>
  </div>
  <nav class="contenedor nav-principal" aria-label="Secciones">
    <a href="/alquileres-mensuales-rio-gallegos">Mensuales</a>
    <a href="/alquiler-temporario-rio-gallegos">Temporarios</a>
    <a href="/departamentos-alquiler-rio-gallegos">Departamentos</a>
    <a href="/casas-alquiler-rio-gallegos">Casas</a>
    <a href="/monoambientes-rio-gallegos">Monoambientes</a>
  </nav>
</header>

<main id="contenido">
<?= $contenido ?>
</main>

<footer class="pie">
  <div class="contenedor pie-columnas">
    <section>
      <h2>Alquileres en Río Gallegos</h2>
      <ul>
        <?php foreach (Landings::fijas() as $path => $landing): ?>
        <li><a href="<?= e($path) ?>"><?= e($landing['h1']) ?></a></li>
        <?php endforeach ?>
      </ul>
    </section>
    <?php if ($barriosFooter): ?>
    <section>
      <h2>Por barrio</h2>
      <ul class="pie-barrios">
        <?php foreach ($barriosFooter as $b): ?>
        <li><a href="<?= e(Catalogo::urlBarrio($b['slug'])) ?>"><?= e($b['nombre']) ?></a></li>
        <?php endforeach ?>
      </ul>
    </section>
    <?php endif ?>
    <section>
      <h2><?= e(app_nombre()) ?></h2>
      <p>Todos los alquileres de la ciudad en un solo lugar. Publicar es gratis.</p>
      <p><a class="boton boton-chico" href="/publicar">Publicá tu aviso</a></p>
    </section>
  </div>
  <p class="contenedor pie-legal">© <?= date('Y') ?> <?= e(app_nombre()) ?>. Los avisos son responsabilidad de cada anunciante.</p>
</footer>
<script src="<?= e(asset('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
