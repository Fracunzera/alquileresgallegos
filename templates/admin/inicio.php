<?php
/** @var array<string, int> $conteo @var int $enCola @var list<array<string, mixed>> $recientes */
use App\Catalogo;
?>
<h1>Panel</h1>
<div class="tablero">
  <a class="tablero-dato" href="/admin/avisos?estado=activo"><strong><?= $conteo['activo'] ?></strong> activos</a>
  <a class="tablero-dato<?= $conteo['pendiente'] ? ' tablero-alerta' : '' ?>" href="/admin/moderacion"><strong><?= $conteo['pendiente'] ?></strong> para moderar</a>
  <a class="tablero-dato<?= $enCola ? ' tablero-alerta' : '' ?>" href="/admin/verificacion"><strong><?= $enCola ?></strong> para verificar</a>
  <?php $den = App\Services\Denuncias::avisosConDenuncias(); ?>
  <?php $vincular = (int) App\Db::valor("SELECT COUNT(*) FROM cuentas WHERE tipo = 'inmobiliaria' AND inmobiliaria_id IS NULL AND estado = 'activa'"); ?>
  <?php if ($vincular): ?><a class="tablero-dato tablero-alerta" href="/admin/cuentas?ver=vincular"><strong><?= $vincular ?></strong> inmobiliarias por vincular</a><?php endif ?>
  <a class="tablero-dato<?= $den ? ' tablero-alerta' : '' ?>" href="/admin/denuncias"><strong><?= $den ?></strong> con denuncias</a>
  <a class="tablero-dato" href="/admin/avisos?estado=pausado"><strong><?= $conteo['pausado'] ?></strong> pausados</a>
  <a class="tablero-dato" href="/admin/avisos?estado=alquilado"><strong><?= $conteo['alquilado'] ?></strong> alquilados</a>
</div>
<p><a class="boton" href="/admin/avisos/nuevo">+ Cargar un aviso</a></p>
<h2>Últimos movimientos</h2>
<?= App\View::parcial('admin/tabla-avisos', ['items' => $recientes]) ?>
