<?php
/**
 * @var array<string, mixed> $inmo @var list<array<string, mixed>> $items @var int $total
 * @var int $pagina @var int $ultima @var string $path @var list<array{0: string, 1: ?string}> $migas
 */
use App\Services\Telefono;
?>
<div class="contenedor">
  <?= App\View::parcial('partials/migas', ['migas' => $migas]) ?>
  <header class="encabezado-inmobiliaria">
    <?php if ($inmo['logo']): ?>
    <img class="logo-inmobiliaria" src="<?= e($inmo['logo']) ?>" alt="Logo de <?= e($inmo['nombre']) ?>" width="96" height="96">
    <?php endif ?>
    <div>
      <h1><?= e($inmo['nombre']) ?></h1>
      <p class="intro"><?= e($inmo['descripcion'] ?: "Inmobiliaria de Río Gallegos con {$total} " . ($total === 1 ? 'propiedad' : 'propiedades') . ' en alquiler publicadas.') ?></p>
      <ul class="datos-inmobiliaria">
        <?php if ($inmo['direccion']): ?><li><?= e($inmo['direccion']) ?>, Río Gallegos</li><?php endif ?>
        <?php if ($inmo['whatsapp']): ?><li><a href="https://wa.me/<?= e($inmo['whatsapp']) ?>" rel="nofollow noopener" target="_blank">WhatsApp <?= e(Telefono::mostrar($inmo['whatsapp'])) ?></a></li><?php endif ?>
        <?php if ($inmo['telefono']): ?><li><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $inmo['telefono'])) ?>">Tel. <?= e($inmo['telefono']) ?></a></li><?php endif ?>
        <?php if ($inmo['email']): ?><li><a href="mailto:<?= e($inmo['email']) ?>"><?= e($inmo['email']) ?></a></li><?php endif ?>
        <?php if ($inmo['web']): ?><li><a href="<?= e($inmo['web']) ?>" rel="nofollow noopener" target="_blank">Sitio web</a></li><?php endif ?>
      </ul>
    </div>
  </header>

  <h2>Propiedades en alquiler de <?= e($inmo['nombre']) ?></h2>
  <?php if ($items): ?>
  <?= App\View::parcial('partials/grilla', ['items' => $items, 'eagerPrimeros' => false]) ?>
  <?= App\View::parcial('partials/paginacion', ['path' => $path, 'pagina' => $pagina, 'ultima' => $ultima]) ?>
  <?php else: ?>
  <p class="vacio">Esta inmobiliaria no tiene avisos activos en este momento.</p>
  <?php endif ?>
</div>
