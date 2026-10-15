<?php /** @var string $email @var int $minutos */ ?>
<div class="contenedor pagina-angosta">
  <h1>Revisá tu email</h1>
  <p class="alerta alerta-ok">Si <strong><?= e($email) ?></strong> tiene una cuenta, te llega un link para entrar. Sirve una sola vez y vence en <?= $minutos ?> minutos.</p>
  <p>Si no lo ves, buscalo en spam o promociones. ¿No llegó? <a href="/cuenta/ingresar">Pedí otro</a>.</p>
</div>
