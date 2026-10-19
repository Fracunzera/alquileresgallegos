<?php
/**
 * @var array<string, mixed> $guia @var string $slug
 * @var array<string, array<string, mixed>> $otras @var list<array{0: string, 1: ?string}> $migas
 */
use App\Seo\Guias;
?>
<article class="contenedor guia">
  <?= App\View::parcial('partials/migas', ['migas' => $migas]) ?>
  <h1><?= e($guia['h1']) ?></h1>
  <p class="intro"><?= e($guia['intro']) ?></p>

  <?php foreach ($guia['secciones'] as [$titulo, $parrafos]): ?>
  <section class="seccion">
    <h2><?= e($titulo) ?></h2>
    <?php foreach ($parrafos as $parrafo): ?>
    <p><?= e($parrafo) ?></p>
    <?php endforeach ?>
  </section>
  <?php endforeach ?>

  <?php if ($guia['fuentes']): ?>
  <section class="seccion guia-fuentes">
    <h2>Fuentes</h2>
    <ul>
      <?php foreach ($guia['fuentes'] as [$nombre, $url]): ?>
      <li><a href="<?= e($url) ?>" rel="noopener" target="_blank"><?= e($nombre) ?></a></li>
      <?php endforeach ?>
    </ul>
  </section>
  <?php endif ?>

  <p class="guia-revision">Revisado el <?= e((new DateTimeImmutable(Guias::REVISADAS_EL))->format('d/m/Y')) ?>. Esta guía es informativa y no reemplaza el consejo de un abogado.</p>

  <section class="seccion llamado">
    <h2>¿Buscás alquiler?</h2>
    <p>Mirá los <a href="/alquileres-mensuales-rio-gallegos">alquileres mensuales</a> y los <a href="/alquiler-temporario-rio-gallegos">temporarios</a> de Río Gallegos, o <a href="/publicar">publicá tu propiedad gratis</a>.</p>
  </section>

  <section class="seccion">
    <h2>Otras guías</h2>
    <ul>
      <?php foreach ($otras as $s => $g): ?>
      <li><a href="<?= e(Guias::url($s)) ?>"><?= e($g['h1']) ?></a></li>
      <?php endforeach ?>
    </ul>
  </section>
</article>
