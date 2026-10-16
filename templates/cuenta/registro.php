<?php /** @var array<string, mixed> $v @var array<string, string> $err @var string $token */
$error = static fn (string $c): string => isset($err[$c]) ? '<p class="error-campo">' . e($err[$c]) . '</p>' : '';
$inmo = ($v['tipo'] ?? '') === 'inmobiliaria';
?>
<div class="contenedor pagina-angosta">
  <h1>Crear una cuenta</h1>
  <p class="intro">Para publicar en <?= e(app_nombre()) ?> necesitás una cuenta. Es gratis: elegís una contraseña y confirmás tu email con un link. Desde tu cuenta publicás, editás y pausás tus avisos, y ves cuántas visitas y consultas tienen.</p>
  <?php if (isset($err['general'])): ?><p class="alerta alerta-error"><?= e($err['general']) ?></p><?php endif ?>
  <form method="post" action="/cuenta/registro" class="formulario">
    <input type="hidden" name="_t" value="<?= e($token) ?>">
    <div class="trampa" aria-hidden="true"><label>No completar <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
    <fieldset>
      <legend>¿Quién publica?</legend>
      <label class="campo-check"><input type="radio" name="tipo" value="particular"<?= $inmo ? '' : ' checked' ?>> Soy dueño/a o particular (hasta <?= App\Services\Cuentas::maxParticular() ?> avisos a la vez)</label>
      <label class="campo-check"><input type="radio" name="tipo" value="inmobiliaria"<?= $inmo ? ' checked' : '' ?>> Soy una inmobiliaria (sin límite de avisos)</label>
      <label class="campo" data-solo-inmobiliaria><span>Nombre de la inmobiliaria</span>
        <input type="text" name="inmobiliaria" value="<?= e($v['inmobiliaria'] ?? '') ?>" maxlength="120">
        <small>La verificamos antes de que puedas publicar.</small>
      </label>
      <?= $error('inmobiliaria') ?>
    </fieldset>
    <fieldset>
      <legend>Tus datos</legend>
      <label class="campo"><span>Nombre</span><input type="text" name="nombre" value="<?= e($v['nombre'] ?? '') ?>" required maxlength="120" autocomplete="name"></label>
      <?= $error('nombre') ?>
      <label class="campo"><span>Email</span><input type="email" name="email" value="<?= e($v['email'] ?? '') ?>" required autocomplete="email"><small>Te mandamos un email para confirmarlo antes de activar la cuenta.</small></label>
      <?= $error('email') ?>
      <div class="fila">
        <label class="campo"><span>Contraseña</span><input type="password" name="clave" required minlength="<?= App\Services\Cuentas::CLAVE_MINIMO ?>" autocomplete="new-password"></label>
        <label class="campo"><span>Repetila</span><input type="password" name="clave_repetida" required minlength="<?= App\Services\Cuentas::CLAVE_MINIMO ?>" autocomplete="new-password"></label>
      </div>
      <small>Mínimo <?= App\Services\Cuentas::CLAVE_MINIMO ?> caracteres.</small>
      <?= $error('clave') ?>
      <label class="campo"><span>WhatsApp (opcional)</span><input type="tel" name="whatsapp" value="<?= e($v['whatsapp'] ?? '') ?>" placeholder="2966 15 123456"><small>Lo completamos solo en tus avisos nuevos.</small></label>
      <?= $error('whatsapp') ?>
    </fieldset>
    <label class="campo-check"><input type="checkbox" name="acepto" value="1"<?= !empty($v['acepto']) ? ' checked' : '' ?> required> Voy a publicar solo propiedades reales en Río Gallegos que puedo alquilar.</label>
    <?= $error('acepto') ?>
    <?= App\Services\Captcha::widget() ?>
    <button class="boton" type="submit">Crear cuenta</button>
  </form>
  <p>¿Ya tenés cuenta? <a href="/cuenta/ingresar">Entrá</a>.</p>
</div>
