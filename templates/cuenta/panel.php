<?php
/**
 * @var array<string, mixed> $cuenta @var list<array<string, mixed>> $avisos @var array<int, array<string, int>> $stats
 * @var bool $puede @var ?string $motivo @var int $diasVerificacion @var list<array{0: string, 1: string}> $flash
 * @var bool $pideClaveActual
 */
use App\Catalogo;
use App\Services\Csrf;
use App\Services\Cuentas;
use App\Services\Telefono;

$etiquetas = ['activo' => 'Publicado', 'pendiente' => 'En revisión', 'pausado' => 'Pausado', 'alquilado' => 'Alquilado', 'rechazado' => 'No aprobado'];
$totalVistas = array_sum(array_column($stats, 'vistas'));
$totalConsultas = array_sum(array_column($stats, 'consultas'));
$boton = static function (int $id, string $accion, string $texto, string $clase = 'boton-secundario'): string {
    return '<form method="post" action="/cuenta/avisos/' . $id . '/' . $accion . '">' . Csrf::campo()
        . '<button class="boton boton-chico ' . $clase . '" type="submit">' . e($texto) . '</button></form>';
};
?>
<div class="contenedor panel-cuenta">
  <div class="titulo-con-accion">
    <h1>Mi cuenta</h1>
    <form method="post" action="/cuenta/salir"><?= Csrf::campo() ?><button class="boton boton-chico boton-secundario" type="submit">Salir</button></form>
  </div>
  <p class="nota">Hola, <?= e($cuenta['nombre']) ?><?= $cuenta['tipo'] === 'inmobiliaria' ? ' · ' . e($cuenta['inmobiliaria_solicitada'] ?? 'Inmobiliaria') : '' ?></p>

  <?php foreach ($flash as [$tipo, $mensaje]): ?>
  <p class="alerta alerta-<?= $tipo === 'ok' ? 'ok' : 'error' ?>"><?= e($mensaje) ?></p>
  <?php endforeach ?>

  <?php if ($puede): ?>
  <p><a class="boton" href="/cuenta/avisos/nuevo">+ Publicar un aviso</a></p>
  <?php else: ?>
  <p class="alerta alerta-error"><?= e($motivo) ?></p>
  <?php endif ?>

  <?php if ($avisos): ?>
  <div class="tablero">
    <div class="tablero-dato"><strong><?= $totalVistas ?></strong> visitas</div>
    <div class="tablero-dato"><strong><?= $totalConsultas ?></strong> consultas</div>
  </div>
  <p class="nota">Últimos 30 días. Las consultas son los clics en WhatsApp, teléfono y email de tus avisos.</p>
  <?php endif ?>

  <h2>Mis avisos</h2>
  <?php if (!$avisos): ?>
  <p class="vacio">Todavía no publicaste avisos.</p>
  <?php endif ?>
  <?php foreach ($avisos as $a): ?>
  <?php
  $id = (int) $a['id'];
  $s = $stats[$id];
  $propio = in_array((string) $a['motivo_estado'], Cuentas::MOTIVOS_DEL_ANUNCIANTE, true);
  $verificar = $a['estado'] === 'activo'
      && strtotime((string) ($a['ultima_verificacion'] ?? $a['publicado_en'] ?? $a['creado_en'])) < strtotime("-{$diasVerificacion} days");
  ?>
  <article class="tarjeta-cuenta">
    <?php if ($a['foto_archivo']): ?>
    <img src="<?= e(Catalogo::urlFoto($a['foto_archivo'], 400)) ?>" alt="" width="96" height="72" loading="lazy">
    <?php else: ?>
    <div class="sin-foto" aria-hidden="true">Sin fotos</div>
    <?php endif ?>
    <div class="tarjeta-cuenta-cuerpo">
      <span class="estado estado-<?= e($a['estado']) ?>"><?= e($etiquetas[$a['estado']]) ?></span>
      <h3><a href="<?= e(Catalogo::urlAviso($id, $a['slug'])) ?>"><?= e($a['titulo']) ?></a></h3>
      <p class="nota"><?= e(Catalogo::precio($a)) ?><?= $a['barrio_nombre'] ? ' · ' . e($a['barrio_nombre']) : '' ?></p>
      <?php if ($a['estado'] === 'activo'): ?>
      <p class="nota"><?= $s['vistas'] ?> visitas · <?= $s['consultas'] ?> consultas (30 días)</p>
      <?php endif ?>
      <?php if ($a['estado'] === 'rechazado' || ($a['estado'] === 'pausado' && !$propio)): ?>
      <p class="error-campo"><?= e($a['motivo_estado'] ?: ($a['estado'] === 'pausado' ? 'Pausado por el equipo del sitio' : 'No aprobado')) ?><?= $a['estado'] === 'pausado' ? ' · Si es un error, respondé el email del sitio.' : ' · Podés corregirlo y se vuelve a revisar.' ?></p>
      <?php endif ?>
      <?php if ($verificar): ?>
      <p class="alerta alerta-error">¿Sigue disponible? Confirmalo para que el aviso siga arriba.</p>
      <?php endif ?>
      <div class="acciones-estado">
        <?php if ($a['estado'] === 'activo'): ?>
        <?= $boton($id, 'disponible', 'Sigue disponible', $verificar ? '' : 'boton-secundario') ?>
        <?= $boton($id, 'alquilado', 'Ya se alquiló') ?>
        <?= $boton($id, 'pausar', 'Pausar') ?>
        <?php elseif (in_array($a['estado'], ['pausado', 'alquilado'], true) && $propio): ?>
        <?= $boton($id, 'reactivar', 'Volver a publicar', '') ?>
        <?php if ($a['estado'] === 'pausado'): ?><?= $boton($id, 'alquilado', 'Ya se alquiló') ?><?php endif ?>
        <?php endif ?>
        <a class="boton boton-chico boton-secundario" href="/cuenta/avisos/<?= $id ?>"><?= $a['estado'] === 'rechazado' ? 'Corregir y reenviar' : 'Editar' ?></a>
      </div>
    </div>
  </article>
  <?php endforeach ?>

  <section class="seccion" id="datos">
    <h2>Mis datos</h2>
    <form method="post" action="/cuenta/datos" class="formulario">
      <?= Csrf::campo() ?>
      <fieldset>
        <p class="nota">Email: <strong><?= e($cuenta['email']) ?></strong> (es con el que entrás)</p>
        <label class="campo"><span>Nombre</span><input type="text" name="nombre" value="<?= e($cuenta['nombre']) ?>" required maxlength="120"></label>
        <div class="fila">
          <label class="campo"><span>WhatsApp</span><input type="tel" name="whatsapp" value="<?= e($cuenta['whatsapp'] ? Telefono::mostrar($cuenta['whatsapp']) : '') ?>"></label>
          <label class="campo"><span>Teléfono</span><input type="tel" name="telefono" value="<?= e($cuenta['telefono'] ?? '') ?>"></label>
        </div>
        <small>Se usan para completar tus avisos nuevos.</small>
      </fieldset>
      <button class="boton boton-secundario" type="submit">Guardar datos</button>
    </form>
  </section>

  <section class="seccion" id="clave">
    <h2>Contraseña</h2>
    <form method="post" action="/cuenta/clave" class="formulario">
      <?= Csrf::campo() ?>
      <fieldset>
        <?php if ($cuenta['password_hash'] === null): ?>
        <p class="nota">Todavía no tenés contraseña. Elegí una para entrar con tu email y contraseña, sin esperar un link.</p>
        <?php endif ?>
        <?php if ($pideClaveActual): ?>
        <label class="campo"><span>Contraseña actual</span><input type="password" name="clave_actual" required autocomplete="current-password"></label>
        <?php endif ?>
        <div class="fila">
          <label class="campo"><span>Contraseña nueva</span><input type="password" name="clave" required minlength="<?= Cuentas::CLAVE_MINIMO ?>" autocomplete="new-password"></label>
          <label class="campo"><span>Repetila</span><input type="password" name="clave_repetida" required minlength="<?= Cuentas::CLAVE_MINIMO ?>" autocomplete="new-password"></label>
        </div>
        <small>Mínimo <?= Cuentas::CLAVE_MINIMO ?> caracteres.</small>
      </fieldset>
      <button class="boton boton-secundario" type="submit"><?= $cuenta['password_hash'] === null ? 'Crear contraseña' : 'Cambiar contraseña' ?></button>
    </form>
  </section>
</div>
