<?php
/**
 * Formulario GET: funciona sin JavaScript. Con JS se auto-envía al cambiar un select.
 *
 * @var string $accion @var array<string, mixed> $fijos @var array<string, string> $query
 * @var list<array<string, mixed>> $barrios
 */
use App\Catalogo;
use App\Filtros;

$sel = fn (string $campo, string $valor) => ($query[$campo] ?? '') === $valor ? ' selected' : '';
?>
<form class="filtros" method="get" action="<?= e($accion) ?>" data-filtros>
  <?php if (!isset($fijos['tipo'])): ?>
  <label>Tipo
    <select name="tipo">
      <option value="">Todos</option>
      <?php foreach (Catalogo::TIPOS as $valor => $nombre): ?>
      <option value="<?= e($valor) ?>"<?= $sel('tipo', $valor) ?>><?= $valor === 'mensual' ? 'Mensual' : 'Temporario' ?></option>
      <?php endforeach ?>
    </select>
  </label>
  <?php endif ?>
  <?php if (!isset($fijos['categoria'])): ?>
  <label>Propiedad
    <select name="categoria">
      <option value="">Todas</option>
      <?php foreach (Catalogo::CATEGORIAS as $valor => $nombre): ?>
      <option value="<?= e($valor) ?>"<?= $sel('categoria', $valor) ?>><?= e($nombre) ?></option>
      <?php endforeach ?>
    </select>
  </label>
  <?php endif ?>
  <?php if (!isset($fijos['barrio_id'])): ?>
  <label>Barrio
    <select name="barrio">
      <option value="">Todos</option>
      <?php foreach ($barrios as $b): ?>
      <option value="<?= e($b['slug']) ?>"<?= $sel('barrio', $b['slug']) ?>><?= e($b['nombre']) ?></option>
      <?php endforeach ?>
    </select>
  </label>
  <?php endif ?>
  <label>Dormitorios
    <select name="dormitorios">
      <option value="">Cualquiera</option>
      <?php for ($n = 1; $n <= 4; $n++): ?>
      <option value="<?= $n ?>"<?= $sel('dormitorios', (string) $n) ?>><?= $n ?> o más</option>
      <?php endfor ?>
    </select>
  </label>
  <label>Precio máximo ($)
    <input type="text" name="precio_max" inputmode="numeric" pattern="[0-9.]*" placeholder="Ej: 600000" value="<?= e($query['precio_max'] ?? '') ?>">
  </label>
  <label>Ordenar
    <select name="orden">
      <?php foreach (Filtros::ORDENES as $valor => $nombre): ?>
      <option value="<?= e($valor) ?>"<?= $sel('orden', $valor) ?>><?= e($nombre) ?></option>
      <?php endforeach ?>
    </select>
  </label>
  <div class="filtros-acciones">
    <button class="boton" type="submit">Filtrar</button>
    <?php if ($query): ?><a href="<?= e($accion === '/buscar' ? '/' : $accion) ?>">Limpiar filtros</a><?php endif ?>
  </div>
</form>
