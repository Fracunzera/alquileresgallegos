<?php /** @var bool $valido @var ?string $nombre @var string $estado */ ?>
<div class="contenedor pagina-angosta">
  <?php if ($valido): ?>
  <h1>¡Hola<?= $nombre ? ', ' . e($nombre) : '' ?>!</h1>
  <form method="post" class="formulario"><button class="boton" type="submit">Entrar a mi cuenta</button></form>
  <?php else: ?>
  <?php if ($estado === 'usado'): ?>
  <h1>Este link ya se usó</h1>
  <p>Cada link sirve una sola vez. Si ya entraste con él, andá a <a href="/cuenta/ingresar">Entrar</a> con tu email y contraseña.</p>
  <?php elseif ($estado === 'vencido'): ?>
  <h1>Este link venció</h1>
  <p>Los links duran <?= App\Services\CuentaAuth::minutosLink() ?> minutos. Pedí uno nuevo y usalo enseguida.</p>
  <?php else: ?>
  <h1>Este link está incompleto</h1>
  <p>Puede que se haya cortado al copiarlo. Volvé al email y tocá el botón, o copiá el link completo hasta el final.</p>
  <?php endif ?>
  <p><a class="boton" href="/cuenta/recuperar">Pedir otro link</a> <a href="/cuenta/ingresar">Entrar con contraseña</a></p>
  <?php endif ?>
</div>
