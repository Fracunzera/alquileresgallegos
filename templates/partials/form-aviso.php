<?php
/**
 * Formulario de aviso compartido entre el panel y /publicar.
 *
 * @var array<string, mixed> $v valores actuales
 * @var array<string, string> $err errores por campo
 * @var list<array<string, mixed>> $barrios
 * @var bool $publico
 * @var list<array<string, mixed>> $inmobiliarias (solo panel)
 * @var bool $esNuevo (solo panel)
 */
use App\Catalogo;

$val = static fn (string $c): string => (string) ($v[$c] ?? '');
$sel = static fn (string $c, string $valor): string => (string) ($v[$c] ?? '') === $valor ? ' selected' : '';
$chk = static fn (string $c): string => !empty($v[$c]) ? ' checked' : '';
$error = static fn (string $c): string => isset($err[$c]) ? '<p class="error-campo" id="error-' . $c . '">' . e($err[$c]) . '</p>' : '';
$inmobiliarias ??= [];
$descripcion = '
  <label class="campo"><span>' . ($publico ? 'Descripción' : 'Texto del aviso') . '</span>
    <textarea name="descripcion" rows="10" required' . ($publico ? ' minlength="80"' : '') . ' placeholder="'
    . ($publico ? 'Contá cómo es: ambientes, calefacción, si está amueblado, si acepta mascotas, requisitos, desde cuándo está disponible…' : 'Pegá acá el texto del posteo o de la web de la inmobiliaria') . '">'
    . e($val('descripcion')) . '</textarea>
    <small>' . ($publico ? 'Mínimo 80 caracteres. Una buena descripción hace que tu aviso aparezca en Google.' : 'Es el contenido que indexa Google: completalo y corregilo si viene muy corto.') . '</small>
  </label>' . $error('descripcion');
?>
<?php if (!$publico): ?>
<fieldset>
  <legend>Texto y origen</legend>
  <?= $descripcion ?>
  <button class="boton boton-secundario" type="submit" name="accion" value="autocompletar" formnovalidate>Autocompletar campos desde el texto</button>
  <label class="campo"><span>Fuente (interno)</span>
    <input type="text" name="fuente_url" value="<?= e($val('fuente_url')) ?>" placeholder="Link al posteo de Facebook o a la web de la inmobiliaria">
    <small>No se muestra en el sitio. Sirve para volver a verificar.</small>
  </label>
  <?= $error('fuente_url') ?>
</fieldset>
<?php endif ?>

<fieldset>
  <legend>La propiedad</legend>
  <label class="campo"><span>Título</span>
    <input type="text" name="titulo" value="<?= e($val('titulo')) ?>" maxlength="140" required placeholder="Ej: Departamento 2 ambientes con cochera en Barrio Fátima">
    <small>Aparece en Google y en la dirección del aviso.</small>
  </label>
  <?= $error('titulo') ?>
  <div class="fila">
    <label class="campo"><span>Alquiler</span>
      <select name="tipo" required>
        <option value="mensual"<?= $sel('tipo', 'mensual') ?>>Mensual</option>
        <option value="temporario"<?= $sel('tipo', 'temporario') ?>>Temporario (por día)</option>
      </select>
    </label>
    <label class="campo"><span>Propiedad</span>
      <select name="categoria" required>
        <option value="">Elegí…</option>
        <?php foreach (Catalogo::CATEGORIAS as $valor => $nombre): ?>
        <option value="<?= e($valor) ?>"<?= $sel('categoria', $valor) ?>><?= e($nombre) ?></option>
        <?php endforeach ?>
      </select>
    </label>
  </div>
  <?= $error('tipo') . $error('categoria') ?>
  <div class="fila-3">
    <label class="campo"><span>Ambientes</span><input type="number" name="ambientes" min="0" max="30" inputmode="numeric" value="<?= e($val('ambientes')) ?>"></label>
    <label class="campo"><span>Dormitorios</span><input type="number" name="dormitorios" min="0" max="30" inputmode="numeric" value="<?= e($val('dormitorios')) ?>"></label>
    <label class="campo"><span>Baños</span><input type="number" name="banos" min="0" max="30" inputmode="numeric" value="<?= e($val('banos')) ?>"></label>
  </div>
  <div class="fila">
    <?php foreach (['acepta_mascotas' => '¿Acepta mascotas?', 'acepta_chicos' => '¿Acepta chicos?'] as $campo => $etiqueta): ?>
    <label class="campo"><span><?= e($etiqueta) ?></span>
      <select name="<?= $campo ?>">
        <option value="">No informa</option>
        <?php foreach (Catalogo::ACEPTA as $valor => $nombre): ?>
        <option value="<?= e($valor) ?>"<?= $sel($campo, $valor) ?>><?= e($nombre) ?></option>
        <?php endforeach ?>
      </select>
    </label>
    <?php endforeach ?>
  </div>
</fieldset>

<fieldset>
  <legend>Precio</legend>
  <div class="fila">
    <label class="campo"><span>Precio<?= $publico ? '' : ' (vacío = consultar)' ?></span>
      <input type="text" name="precio" inputmode="numeric" value="<?= e($val('precio')) ?>" placeholder="Ej: 650000"<?= $publico ? ' required' : '' ?>>
    </label>
    <label class="campo"><span>Moneda</span>
      <select name="moneda">
        <option value="ARS"<?= $sel('moneda', 'ARS') ?>>Pesos</option>
        <option value="USD"<?= $sel('moneda', 'USD') ?>>Dólares</option>
      </select>
    </label>
  </div>
  <?= $error('precio') ?>
  <div class="fila">
    <label class="campo"><span>Precio por</span>
      <select name="periodo">
        <option value="mes"<?= $sel('periodo', 'mes') ?>>Mes</option>
        <option value="dia"<?= $sel('periodo', 'dia') ?>>Día</option>
      </select>
    </label>
    <label class="campo"><span>Expensas ($)</span>
      <input type="text" name="expensas" inputmode="numeric" value="<?= e($val('expensas')) ?>" placeholder="Vacío si no sabés, 0 si no tiene">
    </label>
  </div>
</fieldset>

<fieldset>
  <legend>Ubicación</legend>
  <label class="campo"><span>Barrio</span>
    <select name="barrio_id" required data-barrio>
      <option value="">Elegí el barrio…</option>
      <?php foreach ($barrios as $b): ?>
      <option value="<?= (int) $b['id'] ?>"<?= $sel('barrio_id', (string) $b['id']) ?>><?= e($b['nombre']) ?><?= empty($b['activo']) && isset($b['activo']) ? ' (inactivo)' : '' ?></option>
      <?php endforeach ?>
      <?php if ($publico): ?>
      <option value="otro"<?= $sel('barrio_id', 'otro') ?>>Otro / no está en la lista</option>
      <?php endif ?>
    </select>
  </label>
  <?php if ($publico): ?>
  <label class="campo" data-barrio-otro><span>¿Qué barrio?</span>
    <input type="text" name="barrio_sugerido" value="<?= e($val('barrio_sugerido')) ?>" maxlength="80" placeholder="Solo si elegiste «Otro»">
  </label>
  <?php elseif (!empty($v['barrio_sugerido'])): ?>
  <p class="alerta alerta-error">Quien publicó escribió el barrio «<?= e($v['barrio_sugerido']) ?>». Elegí uno de la lista o <a href="/admin/barrios/nuevo?nombre=<?= e(rawurlencode((string) $v['barrio_sugerido'])) ?>" target="_blank">crealo</a>.</p>
  <?php endif ?>
  <?= $error('barrio_id') ?>
  <label class="campo"><span>Dirección (opcional)</span>
    <input type="text" name="direccion" value="<?= e($val('direccion')) ?>" maxlength="160" placeholder="Calle y altura">
  </label>
  <label class="campo-check"><input type="checkbox" name="mostrar_direccion" value="1"<?= $chk('mostrar_direccion') ?>> Mostrar la dirección exacta en el aviso</label>
  <div class="mapa-selector-bloque campo">
    <span>Marcá la ubicación en el mapa (opcional)</span>
    <div class="mapa-selector" data-mapa-selector></div>
    <small>Tocá el mapa para poner el pin; podés arrastrarlo. Si no mostrás la dirección, en el aviso se ve solo una zona aproximada.</small>
    <button type="button" class="boton boton-chico boton-secundario" data-mapa-quitar hidden>Quitar ubicación</button>
  </div>
  <input type="hidden" name="lat" value="<?= e($val('lat')) ?>">
  <input type="hidden" name="lng" value="<?= e($val('lng')) ?>">
  <?= $error('lat') ?>
</fieldset>

<?php if ($publico): ?>
<fieldset>
  <legend>Descripción</legend>
  <?= $descripcion ?>
</fieldset>
<?php endif ?>

<fieldset>
  <legend>Fotos</legend>
  <label class="campo"><span><?= $publico ? 'Subí fotos (hasta ' . App\Config::int('FOTOS_MAX_POR_AVISO', 12) . ')' : 'Agregar fotos' ?></span>
    <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>JPG, PNG o WebP de hasta <?= App\Config::int('FOTOS_MAX_MB', 8) ?> MB cada una. La primera es la portada.</small>
  </label>
</fieldset>

<fieldset>
  <legend>Contacto</legend>
  <label class="campo"><span>WhatsApp</span>
    <input type="tel" name="contacto_whatsapp" value="<?= e($val('contacto_whatsapp')) ?>" placeholder="Ej: 2966 15 123456" autocomplete="tel">
  </label>
  <?= $error('contacto_whatsapp') ?>
  <label class="campo"><span>Teléfono para llamadas (opcional)</span>
    <input type="tel" name="contacto_telefono" value="<?= e($val('contacto_telefono')) ?>" maxlength="30">
  </label>
  <label class="campo"><span>Email<?= $publico ? ' (no se publica si no querés)' : '' ?></span>
    <input type="email" name="contacto_email" value="<?= e($val('contacto_email')) ?>" maxlength="160" autocomplete="email"<?= $publico ? ' required' : '' ?>>
  </label>
  <?= $error('contacto_email') ?>
  <label class="campo-check"><input type="checkbox" name="ocultar_email" value="1"<?= !empty($v['ocultar_email']) || (isset($v['mostrar_email']) && !$v['mostrar_email']) ? ' checked' : '' ?>> No mostrar el email en el aviso (solo WhatsApp/teléfono)</label>
</fieldset>

<?php if (!$publico): ?>
<fieldset>
  <legend>Publicación</legend>
  <div class="fila">
    <label class="campo"><span>Publica</span>
      <select name="publicador" data-publicador>
        <option value="particular"<?= $sel('publicador', 'particular') ?>>Dueño directo</option>
        <option value="inmobiliaria"<?= $sel('publicador', 'inmobiliaria') ?>>Inmobiliaria</option>
      </select>
    </label>
    <label class="campo"><span>Inmobiliaria</span>
      <select name="inmobiliaria_id">
        <option value="">—</option>
        <?php foreach ($inmobiliarias as $i): ?>
        <option value="<?= (int) $i['id'] ?>"<?= $sel('inmobiliaria_id', (string) $i['id']) ?>><?= e($i['nombre']) ?></option>
        <?php endforeach ?>
      </select>
    </label>
  </div>
  <?= $error('inmobiliaria_id') ?>
  <label class="campo-check"><input type="checkbox" name="destacado" value="1"<?= $chk('destacado') ?>> Destacado (aparece primero en los listados)</label>
  <?php if (!empty($esNuevo)): ?>
  <label class="campo"><span>Estado al guardar</span>
    <select name="estado">
      <option value="activo"<?= $sel('estado', 'activo') ?>>Activo (publicado y verificado hoy)</option>
      <option value="pausado"<?= $sel('estado', 'pausado') ?>>Pausado (borrador)</option>
    </select>
  </label>
  <?php endif ?>
</fieldset>
<?php endif ?>
