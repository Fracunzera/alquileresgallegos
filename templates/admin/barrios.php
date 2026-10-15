<?php /** @var list<array<string, mixed>> $items @var list<array<string, mixed>> $sugeridos */ ?>
<div class="titulo-con-accion">
  <h1>Barrios</h1>
  <a class="boton" href="/admin/barrios/nuevo">+ Nuevo</a>
</div>
<?php if ($sugeridos): ?>
<div class="alerta alerta-error">
  <strong>Barrios escritos a mano en /publicar:</strong>
  <?php foreach ($sugeridos as $s): ?>
  <a href="/admin/barrios/nuevo?nombre=<?= e(rawurlencode($s['barrio_sugerido'])) ?>"><?= e($s['barrio_sugerido']) ?></a> (<?= (int) $s['total'] ?>)
  <?php endforeach ?>
</div>
<?php endif ?>
<div class="tabla-envoltorio">
<table class="tabla">
  <thead><tr><th>Barrio</th><th>Avisos</th><th>Intro propia</th></tr></thead>
  <tbody>
    <?php foreach ($items as $b): ?>
    <tr>
      <td><a href="/admin/barrios/<?= (int) $b['id'] ?>"><strong><?= e($b['nombre']) ?></strong></a><?= $b['activo'] ? '' : ' <span class="estado">inactivo</span>' ?><br><small><?= e(App\Catalogo::urlBarrio($b['slug'])) ?></small></td>
      <td><?= (int) $b['activos'] ?> activos / <?= (int) $b['total'] ?></td>
      <td><?= $b['intro'] ? '✓' : '<small>genérica</small>' ?></td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
