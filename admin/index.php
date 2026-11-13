<?php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();
$n = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$s = [
  'Utilizadores' => $n('SELECT COUNT(*) FROM users'),
  'Perguntas activas' => $n('SELECT COUNT(*) FROM questions WHERE active=1'),
  'Simulações concluídas' => $n('SELECT COUNT(*) FROM attempts WHERE finished_at IS NOT NULL'),
  'Respostas dadas' => $n('SELECT COUNT(*) FROM attempt_answers WHERE chosen IS NOT NULL'),
];
$hard = $pdo->query('SELECT q.id, q.question, COUNT(*) n, ROUND(100*SUM(aa.is_correct)/COUNT(*)) pct FROM attempt_answers aa JOIN questions q ON q.id=aa.question_id WHERE aa.chosen IS NOT NULL GROUP BY q.id, q.question HAVING COUNT(*) >= 3 ORDER BY pct ASC, n DESC LIMIT 10')->fetchAll();
$title = 'Administração';
require __DIR__ . '/../includes/header.php';
?>
<h1>Administração</h1>
<div class="stats">
  <?php foreach ($s as $k => $v): ?><div class="stat"><span><?= $v ?></span><?= e($k) ?></div><?php endforeach; ?>
</div>
<p><a class="btn" href="questions.php">Gerir perguntas</a> <a class="btn ghost" href="users.php">Gerir utilizadores</a></p>
<section class="card">
  <h2>Perguntas com mais erros</h2>
  <?php if (!$hard): ?><p class="muted">Ainda não há dados suficientes (mínimo 3 respostas por pergunta).</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Pergunta</th><th>Respostas</th><th>% certas</th><th></th></tr></thead><tbody>
  <?php foreach ($hard as $h): ?>
    <tr><td><?= e(mb_strimwidth($h['question'], 0, 110, '…')) ?></td><td><?= (int)$h['n'] ?></td><td><?= (int)$h['pct'] ?>%</td><td><a href="question_edit.php?id=<?= (int)$h['id'] ?>">Editar</a></td></tr>
  <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
