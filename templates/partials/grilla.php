<?php /** @var list<array<string, mixed>> $items */ ?>
<div class="grilla">
  <?php foreach ($items as $i => $aviso): ?>
  <?= App\View::parcial('partials/aviso-card', ['aviso' => $aviso, 'eager' => $i < 2 && !empty($eagerPrimeros)]) ?>
  <?php endforeach ?>
</div>
