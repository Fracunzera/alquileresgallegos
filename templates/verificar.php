<?php /** @var ?array<string, mixed> $aviso @var string $token @var ?string $mensaje @var ?string $precio */ ?>
<div class="contenedor pagina-angosta">
  <?php if ($mensaje !== null): ?>
  <h1>Listo</h1>
  <p class="alerta alerta-ok"><?= e($mensaje) ?></p>
  <p><a href="/">Ir a <?= e(app_nombre()) ?></a></p>
  <?php elseif ($aviso === null): ?>
  <h1>Este link ya no es válido</h1>
  <p>Puede que ya hayas respondido o que el aviso se haya dado de baja. Si necesitás cambiar algo, respondé el mensaje que te mandamos.</p>
  <?php else: ?>
  <h1>¿Tu aviso sigue disponible?</h1>
  <div class="tarjeta-admin">
    <strong><?= e($aviso['titulo']) ?></strong>
    <span><?= e($precio) ?><?= $aviso['barrio_nombre'] ? ' · ' . e($aviso['barrio_nombre']) : '' ?></span>
  </div>
  <?php if ($aviso['estado'] !== 'activo'): ?>
  <p>Este aviso está <?= e(App\Catalogo::ESTADOS[$aviso['estado']]) ?>. Si querés volver a publicarlo, respondé el mensaje que te mandamos.</p>
  <?php else: ?>
  <form method="post" class="formulario">
    <button class="boton" type="submit" name="respuesta" value="disponible">Sí, sigue disponible</button>
    <button class="boton boton-secundario" type="submit" name="respuesta" value="alquilado">Ya se alquiló, dar de baja</button>
  </form>
  <?php endif ?>
  <?php endif ?>
</div>
