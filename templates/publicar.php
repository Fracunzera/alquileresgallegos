<?php /** @var array<string, mixed> $v @var array<string, string> $err @var list<array<string, mixed>> $barrios @var string $token */ ?>
<div class="contenedor pagina-angosta">
  <h1>Publicá tu alquiler gratis</h1>
  <p class="intro">Sin registrarte. Te llega un email para confirmar el aviso; después lo revisamos y lo publicamos, normalmente en menos de 24 horas. Te contactan directo por WhatsApp.</p>

  <?php if ($err): ?>
  <div class="alerta alerta-error" role="alert">
    <?php if (isset($err['general'])): ?><p><?= e($err['general']) ?></p><?php endif ?>
    <?php if (array_diff_key($err, ['general' => 1])): ?><p>Revisá los campos marcados en rojo.</p><?php endif ?>
    <p>Por seguridad, volvé a elegir las fotos antes de enviar.</p>
  </div>
  <?php endif ?>

  <form method="post" action="/publicar" enctype="multipart/form-data" class="formulario">
    <input type="hidden" name="_t" value="<?= e($token) ?>">
    <div class="trampa" aria-hidden="true">
      <label>No completar este campo <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label>
    </div>
    <?= App\View::parcial('partials/form-aviso', ['v' => $v, 'err' => $err, 'barrios' => $barrios, 'publico' => true]) ?>
    <label class="campo-check"><input type="checkbox" name="acepto" value="1"<?= !empty($v['acepto']) ? ' checked' : '' ?> required>
      Confirmo que la propiedad está en Río Gallegos, que los datos son reales y que puedo alquilarla.</label>
    <?php if (isset($err['acepto'])): ?><p class="error-campo"><?= e($err['acepto']) ?></p><?php endif ?>
    <button class="boton boton-whatsapp" type="submit">Enviar aviso para revisión</button>
    <p class="nota">Cuando lo aprobemos te avisamos por email. Los avisos se revisan periódicamente: cada tanto te vamos a preguntar si sigue disponible para que el sitio no tenga avisos viejos.</p>
  </form>
</div>
