<?php
/**
 * @var array<string, mixed> $aviso @var list<array<string, mixed>> $fotos @var string $url
 * @var list<array<string, mixed>> $similares @var list<array{0: string, 1: ?string}> $migas
 * @var array{lat: float, lng: float, aproximado: bool}|null $mapa
 */
use App\Catalogo;

$activo = $aviso['estado'] === 'activo';
$expensas = Catalogo::expensas($aviso);
$descripcionCorta = Catalogo::descripcionCorta($aviso);
$verificado = Catalogo::haceCuanto($aviso['ultima_verificacion']);
$publicado = Catalogo::haceCuanto($aviso['publicado_en'] ?? $aviso['creado_en']);
?>
<div class="contenedor ficha"<?= $activo ? ' data-aviso-id="' . (int) $aviso['id'] . '"' : '' ?>>
  <?php if (!in_array($aviso['estado'], ['activo', 'pausado', 'alquilado'], true)): ?>
  <p class="aviso-estado aviso-estado-admin">Vista previa de administrador · estado: <strong><?= e(Catalogo::ESTADOS[$aviso['estado']]) ?></strong>. El público no ve esta página.</p>
  <?php elseif (!$activo): ?>
  <div class="aviso-estado">
    <strong><?= $aviso['estado'] === 'alquilado' ? 'Esta propiedad ya se alquiló.' : 'Este aviso no está disponible por el momento.' ?></strong>
    <?php if ($similares): ?><a href="#similares">Mirá avisos parecidos</a><?php endif ?>
  </div>
  <?php endif ?>

  <?= App\View::parcial('partials/migas', ['migas' => $migas]) ?>

  <?php if ($fotos): ?>
  <div class="galeria" data-galeria>
    <ul class="galeria-pista" aria-label="Fotos de la propiedad">
      <?php foreach ($fotos as $n => $foto): ?>
      <li>
        <img src="<?= e(Catalogo::urlFoto($foto['archivo'], 1200)) ?>"
             srcset="<?= e(Catalogo::urlFoto($foto['archivo'], 400)) ?> 400w, <?= e(Catalogo::urlFoto($foto['archivo'], 1200)) ?> 1200w"
             sizes="(min-width: 960px) 900px, 100vw"
             width="<?= (int) $foto['ancho'] ?>" height="<?= (int) $foto['alto'] ?>"
             alt="<?= e($foto['alt'] ?: $descripcionCorta . ', Río Gallegos. Foto ' . ($n + 1) . ' de ' . count($fotos)) ?>"
             <?= $n === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
      </li>
      <?php endforeach ?>
    </ul>
    <?php if (count($fotos) > 1): ?><p class="galeria-ayuda"><?= count($fotos) ?> fotos · deslizá para ver más</p><?php endif ?>
  </div>
  <?php endif ?>

  <div class="ficha-columnas">
    <div class="ficha-principal">
      <header class="ficha-encabezado">
        <p class="ficha-tipo"><?= e($descripcionCorta) ?><?= $aviso['destacado'] ? ' · <span class="sello sello-linea">Destacado</span>' : '' ?></p>
        <h1><?= e($aviso['titulo']) ?></h1>
        <p class="ficha-precio"><?= e(Catalogo::precio($aviso)) ?></p>
        <?php if ($expensas !== null): ?><p class="ficha-expensas"><?= e($expensas) ?></p><?php endif ?>
      </header>

      <dl class="ficha-datos">
        <div><dt>Tipo</dt><dd><?= $aviso['tipo'] === 'temporario' ? 'Temporario' : 'Mensual' ?></dd></div>
        <div><dt>Propiedad</dt><dd><?= e(Catalogo::CATEGORIAS[$aviso['categoria']]) ?></dd></div>
        <?php if (!empty($aviso['ambientes'])): ?><div><dt>Ambientes</dt><dd><?= (int) $aviso['ambientes'] ?></dd></div><?php endif ?>
        <?php if (!empty($aviso['dormitorios'])): ?><div><dt>Dormitorios</dt><dd><?= (int) $aviso['dormitorios'] ?></dd></div><?php endif ?>
        <?php if (!empty($aviso['banos'])): ?><div><dt>Baños</dt><dd><?= (int) $aviso['banos'] ?></dd></div><?php endif ?>
        <div><dt>Expensas</dt><dd><?= e($expensas ?? 'No informa') ?></dd></div>
        <?php if ($aviso['barrio_slug']): ?>
        <div><dt>Barrio</dt><dd><a href="<?= e(Catalogo::urlBarrio($aviso['barrio_slug'])) ?>"><?= e($aviso['barrio_nombre']) ?></a></dd></div>
        <?php endif ?>
        <?php if ($aviso['mostrar_direccion'] && $aviso['direccion']): ?>
        <div><dt>Dirección</dt><dd><?= e($aviso['direccion']) ?></dd></div>
        <?php endif ?>
      </dl>

      <section class="ficha-descripcion">
        <h2>Descripción</h2>
        <?php foreach (preg_split('/\R{2,}/', trim($aviso['descripcion'])) as $parrafo): ?>
        <p><?= nl2br(e($parrafo)) ?></p>
        <?php endforeach ?>
      </section>

      <?php if ($mapa !== null): ?>
      <section class="ficha-mapa">
        <h2>Ubicación</h2>
        <div class="mapa" data-mapa
             data-lat="<?= e($mapa['lat']) ?>" data-lng="<?= e($mapa['lng']) ?>"
             data-aproximado="<?= $mapa['aproximado'] ? '1' : '0' ?>"></div>
        <p class="nota">
          <?= $mapa['aproximado'] ? 'Ubicación aproximada' : 'Ubicación' ?><?= $aviso['barrio_nombre'] ? ' en ' . e($aviso['barrio_nombre']) : '' ?>, Río Gallegos.
          <a href="https://www.openstreetmap.org/?mlat=<?= e($mapa['lat']) ?>&amp;mlon=<?= e($mapa['lng']) ?>#map=16/<?= e($mapa['lat']) ?>/<?= e($mapa['lng']) ?>" rel="nofollow noopener" target="_blank">Abrir en el mapa</a>
        </p>
      </section>
      <?php endif ?>
    </div>

    <aside class="ficha-lateral">
      <?php if ($activo): ?>
      <div class="caja-contacto" id="contacto">
        <h2>Contactar</h2>
        <?= App\View::parcial('partials/contacto', ['aviso' => $aviso, 'url' => $url]) ?>
        <p class="nota">Decí que lo viste en <?= e(app_nombre()) ?>.</p>
      </div>
      <?php endif ?>
      <div class="caja-publicador">
        <?php if ($aviso['inmobiliaria_slug']): ?>
        <p>Publicado por <a href="<?= e(Catalogo::urlInmobiliaria($aviso['inmobiliaria_slug'])) ?>"><strong><?= e($aviso['inmobiliaria_nombre']) ?></strong></a></p>
        <?php else: ?>
        <p>Publicado por <strong>dueño directo</strong></p>
        <?php endif ?>
        <?php if ($verificado !== null): ?>
        <p class="verificado">✓ Disponibilidad verificada <?= e($verificado) ?></p>
        <?php endif ?>
        <p class="nota">Publicado <?= e($publicado) ?> · Aviso n.º <?= (int) $aviso['id'] ?></p>
        <?php if ($activo): ?>
        <p class="nota"><a href="<?= e(App\Services\Denuncias::urlDenunciar($aviso)) ?>" rel="nofollow">Denunciar este aviso</a></p>
        <?php endif ?>
      </div>
    </aside>
  </div>

  <?php if ($similares): ?>
  <section class="seccion" id="similares">
    <h2>Avisos parecidos</h2>
    <?= App\View::parcial('partials/grilla', ['items' => $similares]) ?>
  </section>
  <?php endif ?>
</div>

<?php if ($activo && $aviso['contacto_whatsapp']): ?>
<div class="barra-contacto">
  <span class="barra-precio"><?= e(Catalogo::precio($aviso, false)) ?></span>
  <?= App\View::parcial('partials/contacto', ['aviso' => $aviso, 'url' => $url, 'soloWhatsapp' => true]) ?>
</div>
<?php endif ?>
