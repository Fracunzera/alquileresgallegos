<?php
/** @var ?string $filtro @var list<array<string, mixed>> $items @var list<array<string, mixed>> $inmobiliarias */
use App\Services\Csrf;
?>
<h1>Cuentas</h1>
<p class="nota">Las cuentas confiables publican sin moderación. Las inmobiliarias publican recién cuando las vinculás a su ficha.</p>
<nav class="pestanas">
  <a href="/admin/cuentas"<?= $filtro === null ? ' aria-current="page"' : '' ?>>Todas</a>
  <a href="/admin/cuentas?ver=vincular"<?= $filtro === 'vincular' ? ' aria-current="page"' : '' ?>>Inmobiliarias por vincular</a>
  <a href="/admin/cuentas?ver=suspendidas"<?= $filtro === 'suspendidas' ? ' aria-current="page"' : '' ?>>Suspendidas</a>
</nav>
<?php if (!$items): ?><p class="vacio">No hay cuentas.</p><?php endif ?>
<?php foreach ($items as $c): ?>
<article class="tarjeta-admin">
  <div class="titulo-con-accion">
    <strong><?= e($c['nombre']) ?> · <?= e($c['email']) ?></strong>
    <span>
      <?php if ($c['estado'] === 'suspendida'): ?><span class="estado estado-rechazado">suspendida</span><?php endif ?>
      <?php if ($c['confiable']): ?><span class="estado estado-activo">confiable</span><?php endif ?>
      <span class="estado"><?= $c['tipo'] === 'inmobiliaria' ? 'inmobiliaria' : 'particular' ?></span>
    </span>
  </div>
  <small>
    <?= (int) $c['activos'] ?> avisos activos de <?= (int) $c['total'] ?> ·
    <a href="/admin/avisos?q=<?= e(rawurlencode($c['email'])) ?>">ver avisos</a> ·
    <?= $c['whatsapp'] ? 'WA ' . e($c['whatsapp']) . ' · ' : '' ?>
    alta <?= e(substr($c['creado_en'], 0, 10)) ?> · último ingreso <?= e($c['ultimo_login'] ? substr($c['ultimo_login'], 0, 16) : 'nunca') ?>
    <?= $c['email_confirmado_en'] ? '' : ' · <strong>email sin confirmar</strong>' ?>
  </small>
  <?php if ($c['tipo'] === 'inmobiliaria'): ?>
    <?php if ($c['inmobiliaria_id']): ?>
    <small>Vinculada a <a href="/admin/inmobiliarias/<?= (int) $c['inmobiliaria_id'] ?>"><?= e($c['inmobiliaria_nombre']) ?></a></small>
    <?php else: ?>
    <div class="alerta alerta-error">
      Pidió publicar como <strong>«<?= e($c['inmobiliaria_solicitada']) ?>»</strong>. Verificá que sea real y vinculala:
      <form method="post" action="/admin/cuentas/<?= (int) $c['id'] ?>/vincular" class="acciones-estado">
        <?= Csrf::campo() ?>
        <select name="inmobiliaria_id" aria-label="Inmobiliaria">
          <option value="">Elegir inmobiliaria existente…</option>
          <?php foreach ($inmobiliarias as $i): ?><option value="<?= (int) $i['id'] ?>"><?= e($i['nombre']) ?></option><?php endforeach ?>
        </select>
        <button class="boton boton-chico" type="submit">Vincular</button>
        <button class="boton boton-chico boton-secundario" type="submit" name="crear" value="1">Crear «<?= e($c['inmobiliaria_solicitada']) ?>» y vincular</button>
      </form>
    </div>
    <?php endif ?>
  <?php endif ?>
  <div class="acciones-estado">
    <form method="post" action="/admin/cuentas/<?= (int) $c['id'] ?>/confiable"><?= Csrf::campo() ?>
      <button class="boton boton-chico boton-secundario" type="submit"><?= $c['confiable'] ? 'Quitar confiable' : 'Marcar confiable' ?></button></form>
    <form method="post" action="/admin/cuentas/<?= (int) $c['id'] ?>/suspender"<?= $c['estado'] === 'activa' ? " onsubmit=\"return confirm('¿Suspender la cuenta y pausar sus avisos?')\"" : '' ?>><?= Csrf::campo() ?>
      <button class="boton boton-chico <?= $c['estado'] === 'activa' ? 'boton-peligro' : 'boton-secundario' ?>" type="submit"><?= $c['estado'] === 'activa' ? 'Suspender' : 'Reactivar cuenta' ?></button></form>
  </div>
</article>
<?php endforeach ?>
