<?php
require_once __DIR__ . '/config.php';
require_login();
$st = db()->prepare('SELECT * FROM attempts WHERE user_id = ? ORDER BY id DESC LIMIT 100');
$st->execute([user()['id']]);
$rows = $st->fetchAll();
$title = 'Histórico';
require __DIR__ . '/includes/header.php';
?>
<h1>Histórico de simulações</h1>
<div class="card">
<?php if (!$rows): ?>
  <p class="muted">Ainda não fez nenhuma simulação. <a href="<?= url('index.php') ?>">Começar agora</a></p>
<?php else: ?>
<div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>#</th><th>Modo</th><th>Matéria</th><th>Perguntas</th><th>Certas</th><th>Nota /20</th><th>Duração</th><th>Estado</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $fin = $r['finished_at'] !== null; ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= $r['mode'] === 'exame' ? 'Exame' : 'Treino' ?></td>
      <td><?= $r['source'] === 'all' ? 'Todas' : ($r['source'] === 'wrong' ? 'Erradas' : 'Parte ' . (int)$r['source']) ?></td>
      <td><?= (int)$r['total'] ?></td>
      <td><?= $fin ? (int)$r['correct'] : '—' ?></td>
      <td><?= $fin ? number_format(grade20((float)$r['score']), 1, ',', '') : '—' ?></td>
      <td><?= $fin ? fmt_time((int)$r['duration']) : '—' ?></td>
      <td><?= $fin ? 'Concluída' : 'Em curso' ?></td>
      <td><a href="<?= url($fin ? 'result.php?id=' . $r['id'] : 'quiz.php?id=' . $r['id']) ?>"><?= $fin ? 'Ver resultado' : 'Continuar' ?></a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
