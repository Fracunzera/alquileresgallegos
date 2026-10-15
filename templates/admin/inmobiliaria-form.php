<?php
/** @var ?array<string, mixed> $inmo @var array<string, mixed> $v @var array<string, string> $err */
use App\Services\Csrf;

$val = static fn (string $c): string => (string) ($v[$c] ?? '');
$error = static fn (string $c): string => isset($err[$c]) ? '<p class="error-campo">' . e($err[$c]) . '</p>' : '';
?>
<h1><?= $inmo ? e($inmo['nombre']) : 'Nueva inmobiliaria' ?></h1>
<form method="post" enctype="multipart/form-data" class="formulario" action="<?= $inmo ? '/admin/inmobiliarias/' . (int) $inmo['id'] : '/admin/inmobiliarias/nueva' ?>">
  <?= Csrf::campo() ?>
  <fieldset>
    <label class="campo"><span>Nombre</span><input type="text" name="nombre" value="<?= e($val('nombre')) ?>" required maxlength="120"></label>
    <?= $error('nombre') ?>
    <label class="campo"><span>URL</span><input type="text" name="slug" value="<?= e($val('slug')) ?>" placeholder="Se genera del nombre"><small>/inmobiliaria/<?= e($val('slug') ?: '…') ?></small></label>
    <?= $error('slug') ?>
    <label class="campo"><span>Descripción (aparece en su página y en Google)</span><textarea name="descripcion" rows="4"><?= e($val('descripcion')) ?></textarea></label>
    <div class="fila">
      <label class="campo"><span>WhatsApp</span><input type="tel" name="whatsapp" value="<?= e($val('whatsapp')) ?>"></label>
      <label class="campo"><span>Teléfono</span><input type="tel" name="telefono" value="<?= e($val('telefono')) ?>"></label>
    </div>
    <?= $error('whatsapp') ?>
    <div class="fila">
      <label class="campo"><span>Email</span><input type="email" name="email" value="<?= e($val('email')) ?>"></label>
      <label class="campo"><span>Sitio web</span><input type="text" name="web" value="<?= e($val('web')) ?>"></label>
    </div>
    <?= $error('email') ?>
    <label class="campo"><span>Dirección</span><input type="text" name="direccion" value="<?= e($val('direccion')) ?>"></label>
    <label class="campo"><span>Logo</span><input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></label>
    <?php if (!empty($v['logo'])): ?><img src="<?= e($v['logo']) ?>" alt="" width="96" height="96" class="logo-inmobiliaria"><?php endif ?>
    <label class="campo-check"><input type="checkbox" name="activo" value="1"<?= !empty($v['activo']) ? ' checked' : '' ?>> Activa (su página es pública)</label>
  </fieldset>
  <div class="botonera"><button class="boton" type="submit">Guardar</button><a href="/admin/inmobiliarias">Cancelar</a></div>
</form>
<?php if ($inmo): ?>
<form method="post" action="/admin/inmobiliarias/<?= (int) $inmo['id'] ?>/borrar" class="zona-peligro" data-confirmar="¿Borrar la inmobiliaria?">
  <?= Csrf::campo() ?><button class="boton boton-peligro boton-chico" type="submit">Borrar</button>
</form>
<?php endif ?>
