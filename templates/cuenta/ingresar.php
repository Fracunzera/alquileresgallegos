<?php /** @var array<string, mixed> $v @var array<string, string> $err @var string $token */ ?>
<div class="contenedor pagina-angosta">
  <h1>Entrar a tu cuenta</h1>
  <?php if (isset($err['general'])): ?><p class="alerta alerta-error"><?= e($err['general']) ?></p><?php endif ?>
  <form method="post" action="/cuenta/ingresar" class="formulario">
    <input type="hidden" name="_t" value="<?= e($token) ?>">
    <div class="trampa" aria-hidden="true"><label>No completar <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
    <label class="campo"><span>Email</span><input type="email" name="email" value="<?= e($v['email'] ?? '') ?>" required autocomplete="username" autofocus></label>
    <label class="campo"><span>Contraseña</span><input type="password" name="clave" required autocomplete="current-password"></label>
    <button class="boton" type="submit">Entrar</button>
  </form>
  <p><a href="/cuenta/recuperar">¿Olvidaste tu contraseña?</a></p>
  <p>¿No tenés cuenta? <a href="/cuenta/registro">Creala gratis</a> para publicar tus avisos.</p>
</div>
