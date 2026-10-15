<?php
/**
 * @var list<array<string, mixed>> $items @var int $total @var ?string $estado @var string $q
 * @var int $pagina @var int $ultima @var array<string, int> $conteo
 */
use App\Catalogo;
?>
<div class="titulo-con-accion">
  <h1>Avisos</h1>
  <a class="boton" href="/admin/avisos/nuevo">+ Nuevo</a>
</div>
<nav class="pestanas">
  <a href="/admin/avisos"<?= $estado === null ? ' aria-current="page"' : '' ?>>Todos</a>
  <?php foreach (Catalogo::ESTADOS as $valor => $nombre): ?>
  <a href="/admin/avisos?estado=<?= e($valor) ?>"<?= $estado === $valor ? ' aria-current="page"' : '' ?>><?= e(ucfirst($valor)) ?> (<?= $conteo[$valor] ?>)</a>
  <?php endforeach ?>
</nav>
<form method="get" class="busqueda-admin">
  <?php if ($estado): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif ?>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por n.º, título, texto o WhatsApp">
  <button class="boton" type="submit">Buscar</button>
</form>
<p class="nota"><?= $total ?> avisos</p>
<?= App\View::parcial('admin/tabla-avisos', ['items' => $items]) ?>
<?= App\View::parcial('partials/paginacion', ['path' => '/admin/avisos', 'pagina' => $pagina, 'ultima' => $ultima, 'query' => array_filter(['estado' => $estado, 'q' => $q])]) ?>
