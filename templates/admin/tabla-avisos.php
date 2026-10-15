<?php
/** @var list<array<string, mixed>> $items */
use App\Catalogo;
?>
<?php if (!$items): ?>
<p class="vacio">No hay avisos.</p>
<?php else: ?>
<div class="tabla-envoltorio">
<table class="tabla">
  <thead><tr><th>N.º</th><th>Aviso</th><th>Estado</th><th>Precio</th><th>Verificado</th></tr></thead>
  <tbody>
    <?php foreach ($items as $a): ?>
    <tr>
      <td><?= (int) $a['id'] ?></td>
      <td>
        <a href="/admin/avisos/<?= (int) $a['id'] ?>"><strong><?= e($a['titulo']) ?></strong></a>
        <?= $a['destacado'] ? '<span class="sello sello-linea">Destacado</span>' : '' ?>
        <br><small><?= e(Catalogo::CATEGORIAS[$a['categoria']]) ?> · <?= e($a['tipo']) ?> · <?= e($a['barrio_nombre'] ?? ('¿' . $a['barrio_sugerido'] . '?')) ?><?= $a['inmobiliaria_nombre'] ? ' · ' . e($a['inmobiliaria_nombre']) : '' ?> · <?= (int) $a['cantidad_fotos'] ?> fotos</small>
      </td>
      <td><span class="estado estado-<?= e($a['estado']) ?>"><?= e($a['estado']) ?></span></td>
      <td><?= e(Catalogo::precio($a, false)) ?></td>
      <td><small><?= e(Catalogo::haceCuanto($a['ultima_verificacion']) ?? '—') ?></small></td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>
