<?php
/**
 * @var list<array<string, mixed>> $items @var list<array<string, mixed>> $barrios @var list<string> $motivos
 * @var list<array<string, mixed>> $sinConfirmar @var int $horas
 */
use App\Catalogo;
use App\Services\Csrf;
?>
<h1>Moderación</h1>
<p class="nota">Avisos enviados desde /publicar con el email confirmado, los más viejos primero. Al aprobar se publican, cuentan como verificados hoy y se le avisa por email a quien publicó.</p>
<?php if (!$items): ?>
<p class="vacio">No hay avisos para moderar.</p>
<?php endif ?>
<?php foreach ($items as $a): ?>
<article class="tarjeta-admin">
  <div class="titulo-con-accion">
    <strong>n.º <?= (int) $a['id'] ?> · <?= e($a['titulo']) ?></strong>
    <small>recibido <?= e(Catalogo::haceCuanto($a['creado_en'])) ?> · <?= e($a['creado_en']) ?></small>
  </div>
  <div>
    <?= e(Catalogo::CATEGORIAS[$a['categoria']]) ?> · <?= e($a['tipo']) ?> · <strong><?= e(Catalogo::precio($a)) ?></strong>
    <?= ($ex = Catalogo::expensas($a)) ? ' · ' . e($ex) : '' ?> · <?= e(Catalogo::ambientes($a)) ?>
    <br>Barrio: <?= $a['barrio_nombre'] ? e($a['barrio_nombre']) : '<strong class="error-campo">«' . e($a['barrio_sugerido']) . '» (escrito a mano)</strong>' ?>
    <?= $a['direccion'] ? ' · ' . e($a['direccion']) . ($a['mostrar_direccion'] ? ' (pública)' : ' (oculta)') : '' ?>
    <?= $a['lat'] ? ' · con ubicación en el mapa' : '' ?>
    <br>Contacto: <?= e(implode(' · ', array_filter([$a['contacto_whatsapp'] ? 'WA ' . $a['contacto_whatsapp'] : null, $a['contacto_telefono'], $a['contacto_email'] . ($a['mostrar_email'] ? '' : ' (oculto)')]))) ?>
    <?php if ($a['otros_mismo_contacto']): ?><br><span class="estado estado-pendiente"><?= (int) $a['otros_mismo_contacto'] ?> aviso(s) más con este WhatsApp</span> <a href="/admin/avisos?q=<?= e($a['contacto_whatsapp']) ?>">ver</a><?php endif ?>
  </div>
  <div class="cita"><?= e($a['descripcion']) ?></div>
  <?php if ($a['fotos']): ?>
  <div class="fotos-moderacion">
    <?php foreach ($a['fotos'] as $foto): ?>
    <img src="<?= e(Catalogo::urlFoto($foto['archivo'], 400)) ?>" alt="" width="120" height="90" loading="lazy">
    <?php endforeach ?>
  </div>
  <?php else: ?>
  <small>Sin fotos</small>
  <?php endif ?>
  <div class="acciones-estado">
    <form method="post" action="/admin/moderacion/<?= (int) $a['id'] ?>/aprobar" class="acciones-estado">
      <?= Csrf::campo() ?>
      <?php if (!$a['barrio_id']): ?>
      <select name="barrio_id" required aria-label="Barrio">
        <option value="">Asignar barrio…</option>
        <?php foreach ($barrios as $b): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['nombre']) ?></option><?php endforeach ?>
      </select>
      <a href="/admin/barrios/nuevo?nombre=<?= e(rawurlencode((string) $a['barrio_sugerido'])) ?>" target="_blank">crear barrio</a>
      <?php endif ?>
      <button class="boton boton-chico" type="submit">Aprobar y publicar</button>
    </form>
    <a class="boton boton-chico boton-secundario" href="/admin/avisos/<?= (int) $a['id'] ?>">Editar</a>
    <a class="boton boton-chico boton-secundario" href="<?= e(Catalogo::urlAviso((int) $a['id'], $a['slug'])) ?>" target="_blank">Vista previa</a>
  </div>
  <form method="post" action="/admin/moderacion/<?= (int) $a['id'] ?>/rechazar" class="acciones-estado">
    <?= Csrf::campo() ?>
    <select name="motivo" aria-label="Motivo del rechazo">
      <?php foreach ($motivos as $motivo): ?><option><?= e($motivo) ?></option><?php endforeach ?>
    </select>
    <button class="boton boton-chico boton-peligro" type="submit">Rechazar</button>
  </form>
  <form method="post" action="/admin/moderacion/<?= (int) $a['id'] ?>/bloquear" onsubmit="return confirm('¿Rechazar y bloquear su WhatsApp, email e IP? No se le avisa.')">
    <?= Csrf::campo() ?>
    <button class="boton boton-chico boton-secundario" type="submit">Rechazar y bloquear (bromas / spam)</button>
  </form>
</article>
<?php endforeach ?>

<?php if ($sinConfirmar): ?>
<details class="seccion">
  <summary><strong><?= count($sinConfirmar) ?> sin confirmar el email</strong> <span class="nota">(se borran solos a las <?= $horas ?> h)</span></summary>
  <div class="tabla-envoltorio">
  <table class="tabla">
    <thead><tr><th>Aviso</th><th>Email</th><th>Recibido</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($sinConfirmar as $s): ?>
      <tr>
        <td><a href="/admin/avisos/<?= (int) $s['id'] ?>">n.º <?= (int) $s['id'] ?> · <?= e($s['titulo']) ?></a></td>
        <td><?= e($s['contacto_email']) ?></td>
        <td><small><?= e($s['creado_en']) ?></small></td>
        <td>
          <form method="post" action="/admin/moderacion/<?= (int) $s['id'] ?>/confirmar-email">
            <?= Csrf::campo() ?><button class="boton boton-chico boton-secundario" type="submit">Pasar a moderación</button>
          </form>
        </td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  </div>
</details>
<?php endif ?>
