<?php
require_once __DIR__ . '/config.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$pdo = db();
$st = $pdo->prepare('SELECT * FROM attempts WHERE id = ? AND user_id = ?');
$st->execute([$id, user()['id']]);
$a = $st->fetch();
if (!$a) { flash('Resultado não encontrado.', 'error'); redirect('history.php'); }
if ($a['finished_at'] === null) {
    if ($a['mode'] === 'exame' && time() - (int)$a['started_ts'] >= (int)$a['time_limit']) { finish_attempt($id); redirect('result.php?id=' . $id); }
    redirect('quiz.php?id=' . $id);
}
$filter = ($_GET['f'] ?? '') === 'wrong' ? 'wrong' : 'all';

$st = $pdo->prepare('SELECT c.id, c.name, COUNT(*) n, SUM(aa.is_correct) ok FROM attempt_answers aa JOIN questions q ON q.id = aa.question_id JOIN categories c ON c.id = q.category_id WHERE aa.attempt_id = ? GROUP BY c.id, c.name ORDER BY c.id');
$st->execute([$id]);
$bycat = $st->fetchAll();

$st = $pdo->prepare('SELECT aa.position, aa.chosen, aa.is_correct, q.question, q.opt_a, q.opt_b, q.opt_c, q.opt_d, q.correct, q.explanation
    FROM attempt_answers aa JOIN questions q ON q.id = aa.question_id WHERE aa.attempt_id = ? ORDER BY aa.position');
$st->execute([$id]);
$rows = $st->fetchAll();

$pct = (float)$a['score'];
$nota = grade20($pct);
$wrong = (int)$a['total'] - (int)$a['correct'];
$unans = 0;
foreach ($rows as $r) if ($r['chosen'] === null) $unans++;
$verdict = $nota >= 14 ? ['Muito bom', 'good'] : ($nota >= 10 ? ['Suficiente', 'mid'] : ['Insuficiente — continue a treinar', 'bad']);

$title = 'Resultado';
require __DIR__ . '/includes/header.php';
?>
<h1>Resultado da simulação #<?= (int)$a['id'] ?></h1>
<div class="card result">
  <div class="score <?= $verdict[1] ?>"><span><?= number_format($nota, 1, ',', '') ?></span><small>/ 20</small></div>
  <div>
    <h2><?= e($verdict[0]) ?></h2>
    <p><b><?= (int)$a['correct'] ?></b> certas · <b><?= $wrong ?></b> erradas<?= $unans ? ' (' . $unans . ' sem resposta)' : '' ?> · <b><?= number_format($pct, 1, ',', '') ?>%</b></p>
    <p class="muted">Modo <?= $a['mode'] === 'exame' ? 'Exame' : 'Treino' ?> · Duração <?= fmt_time((int)$a['duration']) ?> · <?= e($a['finished_at']) ?></p>
    <form method="post" action="<?= url('start.php') ?>" class="inline">
      <?= csrf_field() ?>
      <input type="hidden" name="mode" value="<?= e($a['mode']) ?>">
      <input type="hidden" name="source" value="<?= e($a['source']) ?>">
      <input type="hidden" name="qty" value="<?= (int)$a['total'] ?>">
      <button class="btn" type="submit">Repetir este tipo de simulação</button>
      <a class="btn ghost" href="<?= url('index.php') ?>">Voltar ao painel</a>
    </form>
  </div>
</div>

<section class="card">
  <h2>Desempenho por matéria</h2>
  <?php foreach ($bycat as $c): $p = round($c['ok'] * 100 / $c['n']); $cls = $p >= 70 ? 'good' : ($p >= 50 ? 'mid' : 'bad'); ?>
    <div class="bar-row">
      <div class="bar-label"><span>Parte <?= (int)$c['id'] ?> · <?= e($c['name']) ?></span><b><?= (int)$c['ok'] ?>/<?= (int)$c['n'] ?> (<?= $p ?>%)</b></div>
      <div class="bar"><i class="<?= $cls ?>" style="width:<?= $p ?>%"></i></div>
    </div>
  <?php endforeach; ?>
</section>

<section>
  <div class="tabs">
    <a class="<?= $filter === 'all' ? 'on' : '' ?>" href="?id=<?= $id ?>">Todas as perguntas</a>
    <a class="<?= $filter === 'wrong' ? 'on' : '' ?>" href="?id=<?= $id ?>&f=wrong">Só as erradas (<?= $wrong ?>)</a>
  </div>
  <?php foreach ($rows as $r):
      if ($filter === 'wrong' && $r['is_correct']) continue;
      $opts = ['A' => $r['opt_a'], 'B' => $r['opt_b'], 'C' => $r['opt_c'], 'D' => $r['opt_d']]; ?>
    <article class="card review <?= $r['is_correct'] ? 'r-ok' : 'r-no' ?>">
      <div class="muted small">Pergunta <?= (int)$r['position'] ?> · <?= $r['chosen'] === null ? 'Sem resposta' : ($r['is_correct'] ? 'Certa' : 'Errada') ?></div>
      <h3><?= e($r['question']) ?></h3>
      <ul class="rev-opts">
        <?php foreach ($opts as $L => $t):
            $c = $L === $r['correct'] ? 'ok' : ($L === $r['chosen'] ? 'no' : ''); ?>
          <li class="<?= $c ?>"><b><?= $L ?></b> <?= e($t) ?><?= $L === $r['chosen'] ? ' <em>(a sua resposta)</em>' : '' ?></li>
        <?php endforeach; ?>
      </ul>
      <div class="fb good"><b>Resposta correcta: <?= e($r['correct']) ?></b><p><?= e($r['explanation']) ?></p></div>
    </article>
  <?php endforeach; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
