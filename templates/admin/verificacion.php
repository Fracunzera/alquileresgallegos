<?php
/**
 * @var list<array<string, mixed>> $items @var int $dias @var int $gracia
 * @var ?array{aviso_id: int, link: string, mensaje: string, enviado: bool} $ultima
 */
use App\Catalogo;
use App\Services\Csrf;
?>
<h1>Verificación de disponibilidad</h1>
<p class="nota">Avisos activos sin verificar hace más de <?= $dias ?> días. Si se pide confirmación y no responden en <?= $gracia ?> días,
  <?= App\Config::bool('FRESCURA_AUTODESPUBLICAR') ? 'el cron los pausa automáticamente.' : 'quedan marcados como vencidos (la pausa automática está desactivada: FRESCURA_AUTODESPUBLICAR=0).' ?></p>

<?php if ($ultima): ?>
<div class="tarjeta-admin">
  <strong>Pedido para el aviso n.º <?= (int) $ultima['aviso_id'] ?><?= $ultima['enviado'] ? ' (enviado automáticamente)' : '' ?></strong>
  <div class="cita"><?= e($ultima['mensaje']) ?></div>
  <?php if (!$ultima['enviado']): ?>
  <?php $a = App\Db::fila('SELECT contacto_whatsapp, contacto_email, titulo FROM avisos WHERE id = ?', [$ultima['aviso_id']]); ?>
  <div class="acciones-estado">
    <?php if ($a['contacto_whatsapp']): ?>
    <a class="boton boton-whatsapp boton-chico" target="_blank" rel="noopener" href="https://wa.me/<?= e($a['contacto_whatsapp']) ?>?text=<?= e(rawurlencode($ultima['mensaje'])) ?>">Enviar por WhatsApp</a>
    <?php endif ?>
    <?php if ($a['contacto_email']): ?>
    <a class="boton boton-secundario boton-chico" href="mailto:<?= e($a['contacto_email']) ?>?subject=<?= e(rawurlencode('¿Tu aviso sigue disponible? ' . $a['titulo'])) ?>&amp;body=<?= e(rawurlencode($ultima['mensaje'])) ?>">Enviar por email</a>
    <?php endif ?>
  </div>
  <?php endif ?>
</div>
<?php endif ?>

<?php if (!$items): ?>
<p class="vacio">No hay avisos para verificar. 🎉</p>
<?php endif ?>
<?php foreach ($items as $a): ?>
<?php
$solicitada = $a['verificacion_solicitada_en'];
$vencida = $solicitada !== null && strtotime($solicitada) < strtotime("-{$gracia} days");
?>
<article class="tarjeta-admin">
  <div>
    <a href="/admin/avisos/<?= (int) $a['id'] ?>"><strong>n.º <?= (int) $a['id'] ?> · <?= e($a['titulo']) ?></strong></a><br>
    <small><?= e(Catalogo::precio($a)) ?> · <?= e($a['barrio_nombre'] ?? '') ?><?= $a['inmobiliaria_nombre'] ? ' · ' . e($a['inmobiliaria_nombre']) : '' ?>
      · verificado <?= e(Catalogo::haceCuanto($a['referencia'])) ?>
      <?= $a['contacto_whatsapp'] ? ' · WA ' . e($a['contacto_whatsapp']) : '' ?>
      <?= $a['fuente_url'] ? ' · <a href="' . e($a['fuente_url']) . '" target="_blank" rel="noopener noreferrer">fuente</a>' : '' ?></small>
    <?php if ($solicitada): ?>
    <br><span class="estado <?= $vencida ? 'estado-rechazado' : 'estado-pendiente' ?>">Confirmación pedida <?= e(Catalogo::haceCuanto($solicitada)) ?><?= $vencida ? ' · sin respuesta' : '' ?></span>
    <?php endif ?>
  </div>
  <div class="acciones-estado">
    <?php foreach (['confirmar' => 'Sigue disponible', 'solicitar' => $solicitada ? 'Volver a pedir' : 'Pedir confirmación', 'alquilado' => 'Alquilado', 'pausar' => 'Pausar'] as $accion => $etiqueta): ?>
    <form method="post" action="/admin/verificacion/<?= (int) $a['id'] ?>/<?= $accion ?>">
      <?= Csrf::campo() ?>
      <button class="boton boton-chico<?= $accion === 'confirmar' ? '' : ' boton-secundario' ?>" type="submit"><?= $etiqueta ?></button>
    </form>
    <?php endforeach ?>
  </div>
</article>
<?php endforeach ?>
