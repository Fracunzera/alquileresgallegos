<?php
/** @var array<string, array<string, mixed>> $guias @var list<array{0: string, 1: ?string}> $migas */
use App\Seo\Guias;
?>
<div class="contenedor guia">
  <?= App\View::parcial('partials/migas', ['migas' => $migas]) ?>
  <h1>Guías para alquilar en Río Gallegos</h1>
  <p class="intro">Lo que conviene saber antes de alquilar: qué dice hoy la ley, qué papeles te van a pedir, cómo no caer en una estafa y qué mirar cuando vas a ver una propiedad.</p>
  <ul class="lista-guias">
    <?php foreach ($guias as $slug => $g): ?>
    <li>
      <h2><a href="<?= e(Guias::url($slug)) ?>"><?= e($g['h1']) ?></a></h2>
      <p><?= e($g['description']) ?></p>
    </li>
    <?php endforeach ?>
  </ul>
</div>
