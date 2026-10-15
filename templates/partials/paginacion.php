<?php
/** @var string $path @var int $pagina @var int $ultima @var array<string, string> $query */
if ($ultima <= 1) {
    return;
}
$query ??= [];
$enlace = fn (int $n) => url_con($path, $query + ['pagina' => $n > 1 ? $n : null]);
$desde = max(1, $pagina - 2);
$hasta = min($ultima, $pagina + 2);
?>
<nav class="paginacion" aria-label="Páginas">
  <?php if ($pagina > 1): ?><a rel="prev" href="<?= e($enlace($pagina - 1)) ?>">‹ Anterior</a><?php endif ?>
  <?php if ($desde > 1): ?><a href="<?= e($enlace(1)) ?>">1</a><?php if ($desde > 2): ?><span>…</span><?php endif ?><?php endif ?>
  <?php for ($n = $desde; $n <= $hasta; $n++): ?>
    <?php if ($n === $pagina): ?><span aria-current="page"><?= $n ?></span><?php else: ?><a href="<?= e($enlace($n)) ?>"><?= $n ?></a><?php endif ?>
  <?php endfor ?>
  <?php if ($hasta < $ultima): ?><?php if ($hasta < $ultima - 1): ?><span>…</span><?php endif ?><a href="<?= e($enlace($ultima)) ?>"><?= $ultima ?></a><?php endif ?>
  <?php if ($pagina < $ultima): ?><a rel="next" href="<?= e($enlace($pagina + 1)) ?>">Siguiente ›</a><?php endif ?>
</nav>
