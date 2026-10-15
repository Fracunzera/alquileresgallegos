<?php /** @var ?string $error @var string $email */ use App\Services\Csrf; ?>
<div class="caja-login">
  <h1>Ingresar al panel</h1>
  <?php if ($error): ?><p class="alerta alerta-error"><?= e($error) ?></p><?php endif ?>
  <form method="post" action="/admin/login" class="formulario">
    <?= Csrf::campo() ?>
    <label class="campo"><span>Email</span><input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username" autofocus></label>
    <label class="campo"><span>Contraseña</span><input type="password" name="password" required autocomplete="current-password"></label>
    <button class="boton" type="submit">Ingresar</button>
  </form>
</div>
