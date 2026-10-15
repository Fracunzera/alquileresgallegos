<?php
/** @var array<string, mixed> $aviso */
use App\Catalogo;

$url = Catalogo::urlAviso((int) $aviso['id'], $aviso['slug']);
$expensas = Catalogo::expensas($aviso);
$ambientes = Catalogo::ambientes($aviso);
$eager = !empty($eager);
?>
<article class="tarjeta<?= $aviso['destacado'] ? ' tarjeta-destacada' : '' ?>">
  <div class="tarjeta-foto">
    <?php if ($aviso['foto_archivo']): ?>
    <img src="<?= e(Catalogo::urlFoto($aviso['foto_archivo'], 400)) ?>"
         width="400" height="<?= (int) round(400 * $aviso['foto_alto'] / max(1, $aviso['foto_ancho'])) ?>"
         alt="<?= e(Catalogo::descripcionCorta($aviso) . ', Río Gallegos') ?>"
         <?= $eager ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
    <?php else: ?>
    <div class="sin-foto" aria-hidden="true">Sin fotos</div>
    <?php endif ?>
    <?php if ($aviso['destacado']): ?><span class="sello">Destacado</span><?php endif ?>
    <?php if ((int) $aviso['cantidad_fotos'] > 1): ?><span class="cantidad-fotos"><?= (int) $aviso['cantidad_fotos'] ?> fotos</span><?php endif ?>
  </div>
  <div class="tarjeta-cuerpo">
    <p class="tarjeta-precio"><?= e(Catalogo::precio($aviso)) ?></p>
    <?php if ($expensas !== null): ?><p class="tarjeta-expensas"><?= e($expensas) ?></p><?php endif ?>
    <h3 class="tarjeta-titulo"><a href="<?= e($url) ?>"><?= e($aviso['titulo']) ?></a></h3>
    <p class="tarjeta-datos">
      <?= e(Catalogo::CATEGORIAS[$aviso['categoria']]) ?><?= $ambientes !== '' ? ' · ' . e($ambientes) : '' ?>
    </p>
    <?php if ($aviso['acepta_mascotas'] === 'si' || $aviso['acepta_ninos'] === 'si'): ?>
    <p class="tarjeta-etiquetas">
      <?php if ($aviso['acepta_mascotas'] === 'si'): ?><span>Acepta mascotas</span><?php endif ?>
      <?php if ($aviso['acepta_ninos'] === 'si'): ?><span>Acepta niños</span><?php endif ?>
    </p>
    <?php endif ?>
    <p class="tarjeta-lugar"><?= e($aviso['barrio_nombre'] ?? '') ?> · <?= $aviso['tipo'] === 'temporario' ? 'Temporario' : 'Mensual' ?></p>
  </div>
</article>
