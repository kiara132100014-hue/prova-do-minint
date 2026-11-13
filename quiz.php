<?php
require_once __DIR__ . '/config.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM attempts WHERE id = ? AND user_id = ?');
$st->execute([$id, user()['id']]);
$a = $st->fetch();
if (!$a) { flash('Simulação não encontrada.', 'error'); redirect('index.php'); }
if ($a['finished_at'] === null && $a['mode'] === 'exame' && time() - (int)$a['started_ts'] >= (int)$a['time_limit']) {
    finish_attempt($id);
    $a['finished_at'] = date('c');
}
if ($a['finished_at'] !== null) redirect('result.php?id=' . $id);
$title = $a['mode'] === 'exame' ? 'Exame' : 'Treino';
require __DIR__ . '/includes/header.php';
?>
<div id="quiz" class="quiz">
  <div class="quiz-head">
    <div><span class="pill"><?= $a['mode'] === 'exame' ? 'Modo Exame' : 'Modo Treino' ?></span> <span id="counter" class="muted"></span></div>
    <div id="timer" class="timer" <?= $a['mode'] === 'exame' ? '' : 'hidden' ?>>--:--</div>
  </div>
  <div class="progress"><i id="prog"></i></div>
  <div class="quiz-body">
    <section class="card qcard">
      <div id="qmeta" class="muted small"></div>
      <h2 id="qtext">A carregar…</h2>
      <div id="opts" class="opts"></div>
      <div id="feedback"></div>
      <div class="qnav">
        <button class="btn ghost" id="prev">← Anterior</button>
        <button class="btn" id="next">Seguinte →</button>
        <button class="btn danger" id="finish">Terminar</button>
      </div>
    </section>
    <aside class="card navcard">
      <h3>Perguntas</h3>
      <div id="grid" class="qgrid"></div>
      <p class="muted small">Atalhos: teclas A–D para responder, setas ← → para navegar.</p>
    </aside>
  </div>
</div>
<script>window.QUIZ = {id: <?= (int)$id ?>, csrf: <?= json_encode(csrf_token()) ?>, api: <?= json_encode(url('api.php')) ?>, result: <?= json_encode(url('result.php?id=' . $id)) ?>};</script>
<script src="<?= url('assets/js/quiz.js') ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
