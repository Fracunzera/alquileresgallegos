<?php
/** @var array<string, mixed> $aviso @var string $url @var bool $soloWhatsapp */
use App\Services\Telefono;

$wa = $aviso['contacto_whatsapp'];
$mensaje = 'Hola! Vi tu aviso "' . $aviso['titulo'] . '" en ' . app_nombre() . ' (' . url_abs($url) . '). ¿Sigue disponible?';
$waUrl = $wa ? 'https://wa.me/' . $wa . '?text=' . rawurlencode($mensaje) : null;
?>
<div class="contacto">
  <?php if ($waUrl): ?>
  <a class="boton boton-whatsapp" href="<?= e($waUrl) ?>" rel="nofollow noopener" target="_blank">
    <svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2l-.4-.2Z"/></svg>
    Consultar por WhatsApp
  </a>
  <?php endif ?>
  <?php if (empty($soloWhatsapp)): ?>
  <?php if ($aviso['contacto_telefono']): ?>
  <a class="boton boton-secundario" href="tel:<?= e(preg_replace('/[^\d+]/', '', $aviso['contacto_telefono'])) ?>">Llamar al <?= e($aviso['contacto_telefono']) ?></a>
  <?php elseif ($wa): ?>
  <a class="boton boton-secundario" href="tel:+<?= e($wa) ?>">Llamar al <?= e(Telefono::mostrar($wa)) ?></a>
  <?php endif ?>
  <?php if ($aviso['contacto_email'] && $aviso['mostrar_email']): ?>
  <a class="boton boton-secundario" href="mailto:<?= e($aviso['contacto_email']) ?>?subject=<?= e(rawurlencode('Consulta por: ' . $aviso['titulo'])) ?>">Enviar email</a>
  <?php endif ?>
  <?php endif ?>
</div>
