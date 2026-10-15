<?php
/**
 * @var array<string, mixed> $def @var string $path @var list<array<string, mixed>> $items
 * @var int $total @var int $pagina @var int $ultima @var array<string, string> $query
 * @var list<array<string, mixed>> $barrios @var list<array{0: string, 1: ?string}> $migas
 */
use App\Catalogo;
?>
<div class="contenedor">
  <?= App\View::parcial('partials/migas', ['migas' => $migas]) ?>
  <header class="encabezado-listado">
    <h1><?= e($def['h1']) ?></h1>
    <?php if ($pagina === 1 && !empty($def['intro'])): ?>
    <p class="intro"><?= e($def['intro']) ?></p>
    <?php endif ?>
  </header>

  <details class="filtros-plegable"<?= $query ? ' open' : '' ?>>
    <summary>Filtrar resultados</summary>
    <?= App\View::parcial('partials/filtros', ['accion' => $path, 'fijos' => $def['filtro'], 'query' => $query, 'barrios' => $barrios]) ?>
  </details>

  <p class="resumen-resultados">
    <?= $total === 0 ? 'No hay avisos' : ($total === 1 ? '1 aviso' : Catalogo::numero($total) . ' avisos') ?>
    <?= $pagina > 1 ? "· página {$pagina} de {$ultima}" : '' ?>
  </p>

  <?php if ($items): ?>
  <?= App\View::parcial('partials/grilla', ['items' => $items, 'eagerPrimeros' => $pagina === 1]) ?>
  <?= App\View::parcial('partials/paginacion', ['path' => $path, 'pagina' => $pagina, 'ultima' => $ultima, 'query' => $query]) ?>
  <?php else: ?>
  <div class="vacio">
    <p><?= $query ? 'No encontramos avisos con esos filtros.' : 'Todavía no hay avisos publicados en esta sección.' ?></p>
    <p><a class="boton" href="/publicar">¿Tenés algo para alquilar? Publicalo gratis</a></p>
  </div>
  <?php endif ?>
</div>
