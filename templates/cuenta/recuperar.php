<?php /** @var array<string, mixed> $v @var array<string, string> $err @var string $token */ ?>
<div class="contenedor pagina-angosta">
  <h1>Recuperar contraseña</h1>
  <p class="intro">Te mandamos un link a tu email para entrar a tu cuenta. Después elegís una contraseña nueva en «Mis datos».</p>
  <?php if (isset($err['general'])): ?><p class="alerta alerta-error"><?= e($err['general']) ?></p><?php endif ?>
  <form method="post" action="/cuenta/recuperar" class="formulario">
    <input type="hidden" name="_t" value="<?= e($token) ?>">
    <div class="trampa" aria-hidden="true"><label>No completar <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
    <label class="campo"><span>Email de tu cuenta</span><input type="email" name="email" value="<?= e($v['email'] ?? '') ?>" required autocomplete="email" autofocus></label>
    <?php if (isset($err['email'])): ?><p class="error-campo"><?= e($err['email']) ?></p><?php endif ?>
    <button class="boton" type="submit">Mandame el link</button>
  </form>
  <p class="nota">Abrí el link en el mismo navegador donde querés quedar logueado.</p>
  <p><a href="/cuenta/ingresar">Volver a entrar con contraseña</a></p>
</div>
