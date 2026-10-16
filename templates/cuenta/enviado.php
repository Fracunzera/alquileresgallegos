<?php /** @var string $email @var string $motivo @var int $minutos */ ?>
<div class="contenedor pagina-angosta">
  <h1>Revisá tu email</h1>
  <?php if ($motivo === 'confirmar'): ?>
  <p class="alerta alerta-ok">Te mandamos un email a <strong><?= e($email) ?></strong> para <strong>confirmar tu cuenta</strong>. Tocá el link y quedás adentro; después entrás siempre con tu email y contraseña.</p>
  <?php else: ?>
  <p class="alerta alerta-ok">Si <strong><?= e($email) ?></strong> tiene una cuenta, te llega un link para entrar y elegir una contraseña nueva.</p>
  <?php endif ?>
  <p>El link sirve una sola vez y vence en <?= $minutos ?> minutos. Abrilo en el mismo navegador donde querés quedar logueado (si lo tocás desde la app de correo, puede abrirse en otro).</p>
  <p>Si no lo ves, buscalo en spam o correo no deseado. ¿No llegó? <a href="/cuenta/recuperar">Pedí otro</a>.</p>
</div>
