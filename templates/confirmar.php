<?php /** @var ?array<string, mixed> $aviso @var bool $confirmado */ ?>
<div class="contenedor pagina-angosta">
  <?php if ($confirmado): ?>
  <h1>¡Listo, email confirmado!</h1>
  <p class="alerta alerta-ok">Tu aviso «<?= e($aviso['titulo']) ?>» entró en revisión. Te escribimos cuando esté publicado, normalmente en menos de 24 horas.</p>
  <p><a href="/">Ir a <?= e(app_nombre()) ?></a></p>
  <?php elseif ($aviso === null): ?>
  <h1>Este link ya no es válido</h1>
  <p>Puede que ya hayas confirmado tu aviso o que haya vencido el plazo de <?= App\Services\Confirmacion::horas() ?> horas. Si es así, <a href="/publicar">volvé a publicarlo</a>.</p>
  <?php else: ?>
  <h1>Confirmá tu aviso</h1>
  <div class="tarjeta-admin"><strong><?= e($aviso['titulo']) ?></strong></div>
  <form method="post" class="formulario">
    <button class="boton" type="submit" name="confirmar" value="1">Confirmar y enviar a revisión</button>
  </form>
  <?php endif ?>
</div>
