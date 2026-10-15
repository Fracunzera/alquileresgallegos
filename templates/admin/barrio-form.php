<?php
/** @var ?array<string, mixed> $barrio @var array<string, mixed> $v @var array<string, string> $err */
use App\Services\Csrf;

$val = static fn (string $c): string => (string) ($v[$c] ?? '');
$error = static fn (string $c): string => isset($err[$c]) ? '<p class="error-campo">' . e($err[$c]) . '</p>' : '';
?>
<h1><?= $barrio ? e($barrio['nombre']) : 'Nuevo barrio' ?></h1>
<form method="post" class="formulario" action="<?= $barrio ? '/admin/barrios/' . (int) $barrio['id'] : '/admin/barrios/nuevo' ?>">
  <?= Csrf::campo() ?>
  <fieldset>
    <label class="campo"><span>Nombre</span><input type="text" name="nombre" value="<?= e($val('nombre')) ?>" required maxlength="80"></label>
    <?= $error('nombre') ?>
    <label class="campo"><span>URL</span><input type="text" name="slug" value="<?= e($val('slug')) ?>" placeholder="Se genera del nombre"><small>/alquileres-<?= e($val('slug') ?: '…') ?>-rio-gallegos</small></label>
    <?= $error('slug') ?>
    <label class="campo"><span>Párrafo introductorio de la página del barrio</span>
      <textarea name="intro" rows="5" placeholder="Cómo es el barrio, qué tiene cerca, qué tipo de propiedades hay. Texto propio = mejor posicionamiento."><?= e($val('intro')) ?></textarea>
      <small>Si queda vacío se usa un texto genérico.</small>
    </label>
    <label class="campo"><span>Meta description (opcional, máx. 160)</span><input type="text" name="meta_description" value="<?= e($val('meta_description')) ?>" maxlength="160"></label>
    <label class="campo-check"><input type="checkbox" name="activo" value="1"<?= !empty($v['activo']) ? ' checked' : '' ?>> Activo (aparece en el formulario y tiene página)</label>
  </fieldset>
  <div class="botonera"><button class="boton" type="submit">Guardar</button><a href="/admin/barrios">Cancelar</a></div>
</form>
<?php if ($barrio): ?>
<form method="post" action="/admin/barrios/<?= (int) $barrio['id'] ?>/borrar" class="zona-peligro" data-confirmar="¿Borrar el barrio?">
  <?= Csrf::campo() ?><button class="boton boton-peligro boton-chico" type="submit">Borrar</button>
</form>
<?php endif ?>
