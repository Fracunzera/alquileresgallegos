<?php
/**
 * @var list<array<string, mixed>> $recientes @var int $total @var int $mensuales @var int $temporarios
 * @var list<array<string, mixed>> $barrios @var list<array<string, mixed>> $todosLosBarrios
 * @var list<array<string, mixed>> $inmobiliarias
 */
use App\Catalogo;
?>
<section class="portada">
  <div class="contenedor">
    <h1>Alquileres en Río Gallegos</h1>
    <p class="portada-bajada">Casas, departamentos y monoambientes, mensuales y temporarios. Todo lo que se alquila en la ciudad, en un solo lugar.</p>
    <form class="buscador" method="get" action="/buscar">
      <label class="visualmente-oculto" for="b-tipo">Tipo de alquiler</label>
      <select id="b-tipo" name="tipo">
        <option value="">Mensual o temporario</option>
        <option value="mensual">Mensual</option>
        <option value="temporario">Temporario</option>
      </select>
      <label class="visualmente-oculto" for="b-categoria">Tipo de propiedad</label>
      <select id="b-categoria" name="categoria">
        <option value="">Cualquier propiedad</option>
        <?php foreach (Catalogo::CATEGORIAS as $valor => $nombre): ?>
        <option value="<?= e($valor) ?>"><?= e($nombre) ?></option>
        <?php endforeach ?>
      </select>
      <label class="visualmente-oculto" for="b-barrio">Barrio</label>
      <select id="b-barrio" name="barrio">
        <option value="">Cualquier barrio</option>
        <?php foreach ($todosLosBarrios as $b): ?>
        <option value="<?= e($b['slug']) ?>"><?= e($b['nombre']) ?></option>
        <?php endforeach ?>
      </select>
      <button class="boton" type="submit">Buscar</button>
    </form>
    <ul class="accesos">
      <li><a href="/alquileres-mensuales-rio-gallegos">Mensuales <span><?= $mensuales ?></span></a></li>
      <li><a href="/alquiler-temporario-rio-gallegos">Temporarios <span><?= $temporarios ?></span></a></li>
      <li><a href="/departamentos-alquiler-rio-gallegos">Departamentos</a></li>
      <li><a href="/casas-alquiler-rio-gallegos">Casas</a></li>
      <li><a href="/monoambientes-rio-gallegos">Monoambientes</a></li>
    </ul>
  </div>
</section>

<div class="contenedor">
  <section class="seccion">
    <h2>Últimos avisos publicados</h2>
    <?php if ($recientes): ?>
    <?= App\View::parcial('partials/grilla', ['items' => $recientes, 'eagerPrimeros' => false]) ?>
    <p class="ver-mas"><a href="/alquileres-mensuales-rio-gallegos">Ver todos los mensuales</a> · <a href="/alquiler-temporario-rio-gallegos">Ver todos los temporarios</a></p>
    <?php else: ?>
    <p>Estamos cargando los primeros avisos. <a href="/publicar">Publicá el tuyo gratis</a>.</p>
    <?php endif ?>
  </section>

  <?php if ($barrios): ?>
  <section class="seccion" id="barrios">
    <h2>Alquileres por barrio</h2>
    <ul class="lista-barrios">
      <?php foreach ($barrios as $b): ?>
      <li><a href="<?= e(Catalogo::urlBarrio($b['slug'])) ?>"><?= e($b['nombre']) ?> <span><?= (int) $b['total'] ?></span></a></li>
      <?php endforeach ?>
    </ul>
  </section>
  <?php endif ?>

  <?php if ($inmobiliarias): ?>
  <section class="seccion">
    <h2>Inmobiliarias</h2>
    <ul class="lista-barrios">
      <?php foreach ($inmobiliarias as $i): ?>
      <li><a href="<?= e(Catalogo::urlInmobiliaria($i['slug'])) ?>"><?= e($i['nombre']) ?> <span><?= (int) $i['total'] ?></span></a></li>
      <?php endforeach ?>
    </ul>
  </section>
  <?php endif ?>

  <section class="seccion llamado">
    <h2>¿Tenés una propiedad para alquilar?</h2>
    <p>Publicar es gratis y no hace falta registrarse. Tu aviso aparece en Google y te contactan directo por WhatsApp.</p>
    <p><a class="boton" href="/publicar">Publicar gratis</a></p>
  </section>
</div>
