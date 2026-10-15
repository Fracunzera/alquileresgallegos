<?php
/** @var list<array<string, mixed>> $items @var int $umbral */
use App\Catalogo;
use App\Services\Csrf;
use App\Services\Denuncias;
?>
<h1>Denuncias</h1>
<p class="nota">Un aviso se pausa solo con una denuncia de «usan mi número», o con <?= $umbral ?> denuncias desde conexiones distintas.
  «Descartar» reactiva el aviso si se había pausado por denuncias.</p>
<?php if (!$items): ?>
<p class="vacio">No hay denuncias abiertas.</p>
<?php endif ?>
<?php foreach ($items as $a): ?>
<article class="tarjeta-admin">
  <div class="titulo-con-accion">
    <a href="/admin/avisos/<?= (int) $a['id'] ?>"><strong>n.º <?= (int) $a['id'] ?> · <?= e($a['titulo']) ?></strong></a>
    <span><span class="estado estado-<?= e($a['estado']) ?>"><?= e($a['estado']) ?></span> <?= (int) $a['total'] ?> denuncia(s)</span>
  </div>
  <?php if ($a['motivo_estado'] === Denuncias::MOTIVO_PAUSA): ?><p class="nota"><?= e(Denuncias::MOTIVO_PAUSA) ?></p><?php endif ?>
  <small>Contacto del aviso: <?= e(implode(' · ', array_filter([$a['contacto_whatsapp'], $a['contacto_email']]))) ?> · <a href="<?= e(Catalogo::urlAviso((int) $a['id'], $a['slug'])) ?>" target="_blank">ver aviso</a></small>
  <ul class="historial">
    <?php foreach ($a['denuncias'] as $d): ?>
    <li><strong><?= e(Denuncias::MOTIVOS[$d['motivo']]) ?></strong> · <small><?= e($d['creado_en']) ?></small>
      <?= $d['detalle'] ? '<br>«' . e($d['detalle']) . '»' : '' ?>
      <?= $d['email'] ? '<br><small>Denunciante: <a href="mailto:' . e($d['email']) . '">' . e($d['email']) . '</a></small>' : '' ?></li>
    <?php endforeach ?>
  </ul>
  <div class="acciones-estado">
    <?php foreach (['descartar' => 'Descartar (está bien)', 'alquilado' => 'Marcar alquilado', 'baja' => 'Dar de baja', 'bloquear' => 'Dar de baja y bloquear'] as $accion => $etiqueta): ?>
    <form method="post" action="/admin/denuncias/<?= (int) $a['id'] ?>/<?= $accion ?>"<?= $accion === 'bloquear' ? " data-confirmar=\"¿Dar de baja y bloquear WhatsApp, email e IP del anunciante?\"" : '' ?>>
      <?= Csrf::campo() ?>
      <button class="boton boton-chico<?= $accion === 'descartar' ? ' boton-secundario' : ($accion === 'bloquear' ? ' boton-peligro' : '') ?>" type="submit"><?= $etiqueta ?></button>
    </form>
    <?php endforeach ?>
  </div>
</article>
<?php endforeach ?>
