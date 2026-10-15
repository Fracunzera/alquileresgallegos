<?php /** @var bool $valido @var ?string $nombre */ ?>
<div class="contenedor pagina-angosta">
  <?php if ($valido): ?>
  <h1>¡Hola<?= $nombre ? ', ' . e($nombre) : '' ?>!</h1>
  <form method="post" class="formulario"><button class="boton" type="submit">Entrar a mi cuenta</button></form>
  <?php else: ?>
  <h1>Este link ya no sirve</h1>
  <p>Los links para entrar se usan una sola vez y vencen a los <?= App\Services\CuentaAuth::minutosLink() ?> minutos.</p>
  <p><a class="boton" href="/cuenta/ingresar">Pedir otro link</a></p>
  <?php endif ?>
</div>
