<?php
/**
 * @var ?array<string, mixed> $aviso @var string $token @var ?string $mensaje @var ?string $precio
 * @var ?string $elegida respuesta elegida en el email (disponible | alquilado) @var ?string $error @var bool $pausado
 */
$botones = [
    'disponible' => $pausado ? 'Sigue disponible: volver a publicar' : 'Sí, sigue disponible',
    'alquilado' => 'Ya lo alquilé, dar de baja',
];
if ($elegida === 'alquilado') {
    $botones = array_reverse($botones, true);
}
?>
<div class="contenedor pagina-angosta">
  <?php if ($mensaje !== null): ?>
  <h1>Listo</h1>
  <p class="alerta alerta-ok"><?= e($mensaje) ?></p>
  <p><a href="/">Ir a <?= e(app_nombre()) ?></a></p>
  <?php elseif ($aviso === null): ?>
  <h1>Este link ya no es válido</h1>
  <p>Puede que ya hayas respondido o que el aviso se haya dado de baja. Si tenés cuenta, manejá tus avisos desde <a href="/cuenta">tu cuenta</a>; si no, respondé el mensaje que te mandamos.</p>
  <?php else: ?>
  <h1><?= $pausado ? 'Tu aviso está pausado' : '¿Ya lo alquilaste o sigue disponible?' ?></h1>
  <div class="tarjeta-admin">
    <strong><?= e($aviso['titulo']) ?></strong>
    <span><?= e($precio) ?><?= $aviso['barrio_nombre'] ? ' · ' . e($aviso['barrio_nombre']) : '' ?></span>
  </div>
  <?php if ($error !== null): ?>
  <p class="alerta alerta-error"><?= e($error) ?></p>
  <?php endif ?>
  <?php if ($aviso['estado'] !== 'activo' && !$pausado): ?>
  <p>Este aviso está <?= e(App\Catalogo::ESTADOS[$aviso['estado']]) ?>. Si querés volver a publicarlo, respondé el mensaje que te mandamos.</p>
  <?php else: ?>
  <?php if ($pausado): ?>
  <p>Lo sacamos del sitio porque no recibimos respuesta. Si todavía no lo alquilaste, volvé a publicarlo con un clic.</p>
  <?php endif ?>
  <?php if ($elegida !== null): ?>
  <p>Tocá el botón para confirmar:</p>
  <?php endif ?>
  <form method="post" class="formulario">
    <?php $primero = true; foreach ($botones as $valor => $texto): ?>
    <button class="boton<?= $primero ? '' : ' boton-secundario' ?>" type="submit" name="respuesta" value="<?= e($valor) ?>"><?= e($texto) ?></button>
    <?php $primero = false; endforeach ?>
  </form>
  <?php endif ?>
  <?php endif ?>
</div>
