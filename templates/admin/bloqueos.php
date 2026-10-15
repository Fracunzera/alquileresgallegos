<?php
/** @var list<array<string, mixed>> $items @var int $diasIp */
use App\Services\Bloqueos;
use App\Services\Csrf;
?>
<h1>Bloqueos</h1>
<p class="nota">Lo que llegue por /publicar o por denuncias desde un WhatsApp, email o IP bloqueado se descarta en silencio
  (a la persona se le muestra que salió bien). Los bloqueos de IP vencen a los <?= $diasIp ?> días: en los celulares
  muchas personas comparten la misma IP.</p>

<form method="post" action="/admin/bloqueos" class="tarjeta-admin">
  <?= Csrf::campo() ?>
  <div class="fila-3">
    <label class="campo"><span>Tipo</span>
      <select name="tipo"><?php foreach (Bloqueos::TIPOS as $valor => $nombre): ?><option value="<?= e($valor) ?>"><?= e($nombre) ?></option><?php endforeach ?></select>
    </label>
    <label class="campo"><span>Valor</span><input type="text" name="valor" required placeholder="2966 15 123456 / mail@… / IP"></label>
    <label class="campo"><span>Motivo</span><input type="text" name="motivo" maxlength="255"></label>
  </div>
  <button class="boton boton-chico" type="submit">Bloquear</button>
</form>

<?php if (!$items): ?>
<p class="vacio">No hay nadie bloqueado.</p>
<?php else: ?>
<div class="tabla-envoltorio">
<table class="tabla">
  <thead><tr><th>Bloqueado</th><th>Motivo</th><th>Vence</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $b): ?>
    <tr>
      <td><small><?= e(Bloqueos::TIPOS[$b['tipo']]) ?></small><br><strong><?= e($b['valor']) ?></strong>
        <?= $b['aviso_id'] ? '<br><small><a href="/admin/avisos/' . (int) $b['aviso_id'] . '">aviso n.º ' . (int) $b['aviso_id'] . '</a></small>' : '' ?></td>
      <td><?= e($b['motivo'] ?? '') ?><br><small><?= e($b['creado_en']) ?><?= $b['admin_nombre'] ? ' · ' . e($b['admin_nombre']) : '' ?></small></td>
      <td><?= $b['vence_en'] ? e($b['vence_en']) . ($b['vencido'] ? ' <span class="estado">vencido</span>' : '') : 'nunca' ?></td>
      <td>
        <form method="post" action="/admin/bloqueos/<?= (int) $b['id'] ?>/borrar"><?= Csrf::campo() ?><button class="boton boton-chico boton-secundario" type="submit">Desbloquear</button></form>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>
