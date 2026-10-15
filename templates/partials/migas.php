<?php /** @var list<array{0: string, 1: ?string}> $migas */ ?>
<nav class="migas" aria-label="Ubicación en el sitio">
  <ol>
    <?php foreach ($migas as [$nombre, $href]): ?>
    <li><?php if ($href !== null): ?><a href="<?= e($href) ?>"><?= e($nombre) ?></a><?php else: ?><span aria-current="page"><?= e($nombre) ?></span><?php endif ?></li>
    <?php endforeach ?>
  </ol>
</nav>
