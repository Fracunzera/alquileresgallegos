<?php
/**
 * @var array<string, mixed> $cuenta @var ?array<string, mixed> $aviso @var array<string, mixed> $v
 * @var array<string, string> $err @var list<array<string, mixed>> $fotos @var list<array<string, mixed>> $barrios
 * @var list<array{0: string, 1: string}> $flash
 */
use App\Catalogo;
use App\Services\Csrf;

$accion = $aviso ? '/cuenta/avisos/' . (int) $aviso['id'] : '/cuenta/avisos/nuevo';
?>
<div class="contenedor pagina-angosta">
  <p><a href="/cuenta">← Mi cuenta</a></p>
  <h1><?= $aviso ? 'Editar aviso' : 'Publicar un aviso' ?></h1>
  <?php foreach ($flash as [$tipo, $mensaje]): ?>
  <p class="alerta alerta-<?= $tipo === 'ok' ? 'ok' : 'error' ?>"><?= e($mensaje) ?></p>
  <?php endforeach ?>
  <?php if ($err): ?><p class="alerta alerta-error" role="alert">Revisá los campos marcados en rojo. Por seguridad, volvé a elegir las fotos.</p><?php endif ?>
  <?php if (!$aviso && !$cuenta['confiable']): ?>
  <p class="nota">Revisamos los avisos antes de publicarlos, normalmente en menos de 24 horas.</p>
  <?php endif ?>
  <?php if ($aviso && $aviso['estado'] !== 'activo'): ?>
  <p class="nota">Estado: <?= e(Catalogo::ESTADOS[$aviso['estado']]) ?>. <a href="<?= e(Catalogo::urlAviso((int) $aviso['id'], $aviso['slug'])) ?>" target="_blank">Ver cómo queda</a></p>
  <?php endif ?>
  <form method="post" action="<?= e($accion) ?>" enctype="multipart/form-data" class="formulario">
    <?= Csrf::campo() ?>
    <?= App\View::parcial('partials/form-aviso', ['v' => $v, 'err' => $err, 'barrios' => $barrios, 'publico' => true]) ?>
    <button class="boton boton-whatsapp" type="submit"><?= $aviso ? 'Guardar cambios' : 'Publicar aviso' ?></button>
  </form>
  <?php if ($aviso): ?>
  <?= App\View::parcial('partials/fotos-editar', ['fotos' => $fotos, 'accion' => '/cuenta/avisos/' . (int) $aviso['id'] . '/fotos']) ?>
  <?php endif ?>
</div>
