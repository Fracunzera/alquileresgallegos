<?php
/**
 * @var ?array<string, mixed> $aviso @var array<string, mixed> $v @var array<string, string> $err
 * @var ?string $mensaje @var list<array<string, mixed>> $fotos @var list<array<string, mixed>> $barrios
 * @var list<array<string, mixed>> $inmobiliarias @var list<array<string, mixed>> $historial
 */
use App\Catalogo;
use App\Services\Csrf;

$accion = $aviso ? '/admin/avisos/' . (int) $aviso['id'] : '/admin/avisos/nuevo';
?>
<div class="titulo-con-accion">
  <h1><?= $aviso ? 'Aviso n.º ' . (int) $aviso['id'] : 'Nuevo aviso' ?></h1>
  <?php if ($aviso): ?>
  <span>
    <span class="estado estado-<?= e($aviso['estado']) ?>"><?= e(Catalogo::ESTADOS[$aviso['estado']]) ?></span>
    <a href="<?= e(Catalogo::urlAviso((int) $aviso['id'], $aviso['slug'])) ?>" target="_blank">Ver en el sitio ↗</a>
  </span>
  <?php endif ?>
</div>
<?php if ($mensaje): ?><p class="alerta alerta-ok"><?= e($mensaje) ?></p><?php endif ?>
<?php if ($err): ?><p class="alerta alerta-error">Revisá los campos marcados.</p><?php endif ?>
<?php if (!empty($estadisticas)): ?>
<p class="nota">Últimos 30 días: <strong><?= $estadisticas['vistas'] ?></strong> visitas · <strong><?= $estadisticas['consultas'] ?></strong> consultas
  (<?= $estadisticas['whatsapp'] ?> WhatsApp, <?= $estadisticas['telefono'] ?> teléfono, <?= $estadisticas['email'] ?> email)</p>
<?php endif ?>
<?php if ($aviso && $aviso['motivo_estado']): ?><p class="nota">Motivo del estado: <?= e($aviso['motivo_estado']) ?></p><?php endif ?>

<?php if ($aviso): ?>
<div class="acciones-estado">
  <?php foreach (['activo' => 'Publicar / activar', 'pausado' => 'Pausar', 'alquilado' => 'Marcar alquilado'] as $estado => $etiqueta): ?>
  <?php if ($aviso['estado'] !== $estado): ?>
  <form method="post" action="/admin/avisos/<?= (int) $aviso['id'] ?>/estado">
    <?= Csrf::campo() ?><input type="hidden" name="estado" value="<?= $estado ?>">
    <button class="boton boton-chico<?= $estado === 'activo' ? '' : ' boton-secundario' ?>" type="submit"><?= $etiqueta ?></button>
  </form>
  <?php endif ?>
  <?php endforeach ?>
  <form method="post" action="/admin/avisos/<?= (int) $aviso['id'] ?>/destacar">
    <?= Csrf::campo() ?><button class="boton boton-chico boton-secundario" type="submit"><?= $aviso['destacado'] ? 'Quitar destacado' : 'Destacar' ?></button>
  </form>
</div>
<?php endif ?>

<form method="post" action="<?= e($accion) ?>" enctype="multipart/form-data" class="formulario">
  <?= Csrf::campo() ?>
  <?= App\View::parcial('partials/form-aviso', ['v' => $v, 'err' => $err, 'barrios' => $barrios, 'inmobiliarias' => $inmobiliarias, 'publico' => false, 'esNuevo' => $aviso === null]) ?>
  <div class="botonera">
    <button class="boton" type="submit" name="accion" value="guardar">Guardar</button>
    <button class="boton boton-secundario" type="submit" name="accion" value="guardar_y_otro">Guardar y cargar otro</button>
  </div>
  <p class="nota">«Guardar y cargar otro» abre un aviso nuevo con el mismo tipo, barrio, contacto e inmobiliaria.</p>
</form>

<?php if ($aviso): ?>
<?= App\View::parcial('partials/fotos-editar', ['fotos' => $fotos, 'accion' => '/admin/avisos/' . (int) $aviso['id'] . '/fotos']) ?>

<?php if ($historial): ?>
<section class="seccion">
  <h2>Historial de verificación</h2>
  <ul class="historial">
    <?php foreach ($historial as $h): ?>
    <li><small><?= e($h['creado_en']) ?></small> · <?= e(str_replace('_', ' ', $h['accion'])) ?> (<?= e($h['canal']) ?>)<?= $h['admin_nombre'] ? ' · ' . e($h['admin_nombre']) : '' ?><?= $h['nota'] ? ' · ' . e($h['nota']) : '' ?></li>
    <?php endforeach ?>
  </ul>
</section>
<?php endif ?>

<section class="seccion zona-peligro">
  <form method="post" action="/admin/avisos/<?= (int) $aviso['id'] ?>/borrar" data-confirmar="¿Borrar definitivamente este aviso y sus fotos?">
    <?= Csrf::campo() ?>
    <button class="boton boton-peligro boton-chico" type="submit">Borrar aviso</button>
    <span class="nota">Para sacarlo del sitio alcanza con pausarlo o marcarlo alquilado.</span>
  </form>
</section>
<?php endif ?>
