<?php /** @var int $codigo @var string $titulo @var string $texto */ ?>
<div class="contenedor pagina-error">
  <h1><?= e($titulo) ?></h1>
  <?php if ($texto !== ''): ?><p><?= e($texto) ?></p><?php endif ?>
  <p>
    <a class="boton" href="/alquileres-mensuales-rio-gallegos">Ver alquileres mensuales</a>
    <a class="boton boton-secundario" href="/alquiler-temporario-rio-gallegos">Ver temporarios</a>
  </p>
</div>
