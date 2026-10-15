<?php
/** @var array<string, int> $conteo @var int $enCola @var list<array<string, mixed>> $recientes */
use App\Catalogo;
?>
<h1>Panel</h1>
<div class="tablero">
  <a class="tablero-dato" href="/admin/avisos?estado=activo"><strong><?= $conteo['activo'] ?></strong> activos</a>
  <a class="tablero-dato<?= $conteo['pendiente'] ? ' tablero-alerta' : '' ?>" href="/admin/moderacion"><strong><?= $conteo['pendiente'] ?></strong> para moderar</a>
  <a class="tablero-dato<?= $enCola ? ' tablero-alerta' : '' ?>" href="/admin/verificacion"><strong><?= $enCola ?></strong> para verificar</a>
  <a class="tablero-dato" href="/admin/avisos?estado=pausado"><strong><?= $conteo['pausado'] ?></strong> pausados</a>
  <a class="tablero-dato" href="/admin/avisos?estado=alquilado"><strong><?= $conteo['alquilado'] ?></strong> alquilados</a>
</div>
<p><a class="boton" href="/admin/avisos/nuevo">+ Cargar un aviso</a></p>
<h2>Últimos movimientos</h2>
<?= App\View::parcial('admin/tabla-avisos', ['items' => $recientes]) ?>
