<?php
/**
 * @var array<string, mixed> $aviso @var string $url @var array<string, mixed> $v
 * @var array<string, string> $err @var bool $enviada @var string $token
 */
use App\Services\Denuncias;

$error = static fn (string $c): string => isset($err[$c]) ? '<p class="error-campo">' . e($err[$c]) . '</p>' : '';
?>
<div class="contenedor pagina-angosta">
  <?php if ($enviada): ?>
  <h1>Gracias por avisarnos</h1>
  <p class="alerta alerta-ok">Vamos a revisar el aviso. Si hace falta, lo damos de baja.</p>
  <p><a href="/">Volver al inicio</a></p>
  <?php else: ?>
  <h1>Denunciar aviso</h1>
  <p class="tarjeta-admin"><a href="<?= e($url) ?>"><strong><?= e($aviso['titulo']) ?></strong></a></p>
  <?php if (isset($err['general'])): ?><p class="alerta alerta-error"><?= e($err['general']) ?></p><?php endif ?>
  <form method="post" class="formulario">
    <input type="hidden" name="_t" value="<?= e($token) ?>">
    <div class="trampa" aria-hidden="true"><label>No completar <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
    <fieldset>
      <legend>¿Qué pasa con este aviso?</legend>
      <?php foreach (Denuncias::MOTIVOS as $valor => $texto): ?>
      <label class="campo-check"><input type="radio" name="motivo" value="<?= e($valor) ?>"<?= ($v['motivo'] ?? '') === $valor ? ' checked' : '' ?> required> <?= e($texto) ?></label>
      <?php endforeach ?>
      <?= $error('motivo') ?>
      <label class="campo"><span>Detalle (opcional)</span>
        <textarea name="detalle" rows="4" maxlength="500" placeholder="Contanos lo que sepas"><?= e($v['detalle'] ?? '') ?></textarea>
      </label>
      <?= $error('detalle') ?>
      <label class="campo"><span>Tu email (opcional, obligatorio si usan tu número)</span>
        <input type="email" name="email" value="<?= e($v['email'] ?? '') ?>" maxlength="160" autocomplete="email">
        <small>Solo lo usamos para contactarte por esta denuncia.</small>
      </label>
      <?= $error('email') ?>
    </fieldset>
    <?= App\Services\Captcha::widget() ?>
    <button class="boton" type="submit">Enviar denuncia</button>
  </form>
  <?php endif ?>
</div>
