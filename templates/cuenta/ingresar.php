<?php /** @var array<string, mixed> $v @var array<string, string> $err @var string $token */ ?>
<div class="contenedor pagina-angosta">
  <h1>Entrar a tu cuenta</h1>
  <p class="intro">Sin contraseña: te mandamos un link a tu email para entrar.</p>
  <?php if (isset($err['general'])): ?><p class="alerta alerta-error"><?= e($err['general']) ?></p><?php endif ?>
  <form method="post" action="/cuenta/ingresar" class="formulario">
    <input type="hidden" name="_t" value="<?= e($token) ?>">
    <div class="trampa" aria-hidden="true"><label>No completar <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
    <label class="campo"><span>Email</span><input type="email" name="email" value="<?= e($v['email'] ?? '') ?>" required autocomplete="email" autofocus></label>
    <?php if (isset($err['email'])): ?><p class="error-campo"><?= e($err['email']) ?></p><?php endif ?>
    <button class="boton" type="submit">Mandame el link</button>
  </form>
  <p>¿No tenés cuenta? <a href="/cuenta/registro">Creala gratis</a> para publicar y administrar varios avisos.</p>
</div>
