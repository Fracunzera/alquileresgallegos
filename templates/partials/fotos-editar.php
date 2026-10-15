<?php
/** @var list<array<string, mixed>> $fotos @var string $accion URL del POST */
use App\Catalogo;
use App\Services\Csrf;
?>
<section class="seccion" id="fotos">
  <h2>Fotos (<?= count($fotos) ?>)</h2>
  <?php if ($fotos): ?>
  <form method="post" action="<?= e($accion) ?>" class="fotos-admin">
    <?= Csrf::campo() ?>
    <?php foreach ($fotos as $foto): ?>
    <div class="foto-admin">
      <img src="<?= e(Catalogo::urlFoto($foto['archivo'], 400)) ?>" alt="" width="200" height="<?= (int) round(200 * $foto['alto'] / max(1, $foto['ancho'])) ?>" loading="lazy">
      <label class="campo"><span>Orden</span><input type="number" name="orden[<?= (int) $foto['id'] ?>]" value="<?= (int) $foto['orden'] ?>" min="0"></label>
      <label class="campo"><span>Texto alternativo</span><input type="text" name="alt[<?= (int) $foto['id'] ?>]" value="<?= e($foto['alt']) ?>" placeholder="Automático si queda vacío" maxlength="200"></label>
      <label class="campo-check"><input type="checkbox" name="borrar[]" value="<?= (int) $foto['id'] ?>"> Borrar</label>
    </div>
    <?php endforeach ?>
    <button class="boton" type="submit">Guardar fotos</button>
  </form>
  <?php else: ?>
  <p class="nota">Sin fotos. Agregalas desde el formulario de arriba.</p>
  <?php endif ?>
</section>
