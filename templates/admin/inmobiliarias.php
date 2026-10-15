<?php /** @var list<array<string, mixed>> $items */ ?>
<div class="titulo-con-accion">
  <h1>Inmobiliarias</h1>
  <a class="boton" href="/admin/inmobiliarias/nueva">+ Nueva</a>
</div>
<?php if (!$items): ?>
<p class="vacio">Todavía no cargaste inmobiliarias.</p>
<?php else: ?>
<div class="tabla-envoltorio">
<table class="tabla">
  <thead><tr><th>Nombre</th><th>Contacto</th><th>Avisos</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $i): ?>
    <tr>
      <td><a href="/admin/inmobiliarias/<?= (int) $i['id'] ?>"><strong><?= e($i['nombre']) ?></strong></a><?= $i['activo'] ? '' : ' <span class="estado">inactiva</span>' ?><br><small>/inmobiliaria/<?= e($i['slug']) ?></small></td>
      <td><small><?= e($i['whatsapp'] ?? '') ?> <?= e($i['telefono'] ?? '') ?></small></td>
      <td><?= (int) $i['activos'] ?> activos / <?= (int) $i['total'] ?></td>
      <td><a href="/admin/avisos/nuevo?inmobiliaria=<?= (int) $i['id'] ?>">+ aviso</a></td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>
