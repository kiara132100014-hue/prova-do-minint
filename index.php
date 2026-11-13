<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();
$uid = user()['id'];

$st = $pdo->prepare('SELECT COUNT(*) n, COALESCE(AVG(score),0) avg_score, COALESCE(MAX(score),0) best FROM attempts WHERE user_id = ? AND finished_at IS NOT NULL');
$st->execute([$uid]);
$ov = $st->fetch();

$st = $pdo->prepare('SELECT COUNT(*) answered, COALESCE(SUM(aa.is_correct),0) right_n FROM attempt_answers aa JOIN attempts a ON a.id = aa.attempt_id WHERE a.user_id = ? AND aa.chosen IS NOT NULL');
$st->execute([$uid]);
$an = $st->fetch();
$acc = $an['answered'] > 0 ? round($an['right_n'] * 100 / $an['answered']) : 0;

$st = $pdo->prepare("SELECT c.id, c.name,
        (SELECT COUNT(*) FROM questions q WHERE q.category_id = c.id AND q.active = 1) AS qn,
        COUNT(aa.id) AS answered, COALESCE(SUM(aa.is_correct),0) AS right_n
    FROM categories c
    LEFT JOIN questions q2 ON q2.category_id = c.id
    LEFT JOIN attempt_answers aa ON aa.question_id = q2.id AND aa.chosen IS NOT NULL
         AND aa.attempt_id IN (SELECT id FROM attempts WHERE user_id = ?)
    GROUP BY c.id, c.name ORDER BY c.id");
$st->execute([$uid]);
$cats = $st->fetchAll();

$st = $pdo->prepare('SELECT id FROM attempts WHERE user_id = ? AND finished_at IS NULL ORDER BY id DESC LIMIT 1');
$st->execute([$uid]);
$open = $st->fetchColumn();

$title = 'Painel';
require __DIR__ . '/includes/header.php';
?>
<h1>Olá, <?= e(explode(' ', user()['name'])[0]) ?> 👋</h1>
<p class="muted">Prepare-se para a prova escrita do Ministério do Interior com perguntas dos materiais do concurso.</p>

<?php if ($open): ?>
<div class="alert info">Tem uma simulação por terminar. <a href="<?= url('quiz.php?id=' . (int)$open) ?>"><b>Continuar agora</b></a></div>
<?php endif; ?>

<div class="stats">
  <div class="stat"><span><?= (int)$ov['n'] ?></span>Simulações concluídas</div>
  <div class="stat"><span><?= number_format(grade20((float)$ov['avg_score']), 1, ',', '') ?></span>Nota média (0–20)</div>
  <div class="stat"><span><?= number_format(grade20((float)$ov['best']), 1, ',', '') ?></span>Melhor nota (0–20)</div>
  <div class="stat"><span><?= $acc ?>%</span>Taxa de acerto global</div>
</div>

<div class="grid2">
  <section class="card">
    <h2>Iniciar simulação</h2>
    <form method="post" action="<?= url('start.php') ?>" class="form">
      <?= csrf_field() ?>
      <label>Modo
        <select name="mode">
          <option value="treino">Treino — resposta e explicação imediatas</option>
          <option value="exame">Exame — cronómetro (<?= SECONDS_PER_QUESTION ?> s por pergunta), correcção no fim</option>
        </select>
      </label>
      <label>Matéria
        <select name="source">
          <option value="all">Todas as matérias (aleatório)</option>
          <option value="wrong">Só as perguntas que errei</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>">Parte <?= (int)$c['id'] ?> — <?= e($c['name']) ?> (<?= (int)$c['qn'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Número de perguntas
        <select name="qty">
          <?php foreach ([10, 20, 30, 50, 100, 168] as $q): ?><option value="<?= $q ?>" <?= $q === 20 ? 'selected' : '' ?>><?= $q ?></option><?php endforeach; ?>
        </select>
      </label>
      <button class="btn big" type="submit">Começar</button>
    </form>
  </section>

  <section class="card">
    <h2>Desempenho por matéria</h2>
    <?php foreach ($cats as $c):
        $p = $c['answered'] > 0 ? round($c['right_n'] * 100 / $c['answered']) : 0;
        $cls = $c['answered'] == 0 ? 'none' : ($p >= 70 ? 'good' : ($p >= 50 ? 'mid' : 'bad')); ?>
      <div class="bar-row">
        <div class="bar-label"><span>Parte <?= (int)$c['id'] ?> · <?= e($c['name']) ?></span><b><?= $c['answered'] > 0 ? $p . '%' : '—' ?></b></div>
        <div class="bar"><i class="<?= $cls ?>" style="width:<?= $p ?>%"></i></div>
      </div>
    <?php endforeach; ?>
    <p class="muted small">Calculado com base em todas as respostas que já deu.</p>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
