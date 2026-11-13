<?php
require_once __DIR__ . '/config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_check();
$pdo = db();
$uid = user()['id'];
$mode = ($_POST['mode'] ?? '') === 'exame' ? 'exame' : 'treino';
$src = (string)($_POST['source'] ?? 'all');
$qty = max(5, min(168, (int)($_POST['qty'] ?? 20)));

if ($src === 'wrong') {
    $sql = "SELECT q.id FROM questions q
            JOIN attempt_answers aa ON aa.question_id = q.id
            JOIN attempts a ON a.id = aa.attempt_id
            WHERE a.user_id = :u AND q.active = 1 AND aa.chosen IS NOT NULL AND aa.is_correct = 0
              AND aa.id = (SELECT MAX(x.id) FROM attempt_answers x JOIN attempts y ON y.id = x.attempt_id
                           WHERE y.user_id = :u2 AND x.question_id = q.id AND x.chosen IS NOT NULL)
            ORDER BY RAND() LIMIT $qty";
    $st = $pdo->prepare($sql);
    $st->execute([':u' => $uid, ':u2' => $uid]);
} elseif (ctype_digit($src)) {
    $st = $pdo->prepare("SELECT id FROM questions WHERE active = 1 AND category_id = ? ORDER BY RAND() LIMIT $qty");
    $st->execute([(int)$src]);
} else {
    $src = 'all';
    $st = $pdo->query("SELECT id FROM questions WHERE active = 1 ORDER BY RAND() LIMIT $qty");
}
$ids = $st->fetchAll(PDO::FETCH_COLUMN);
if (!$ids) {
    flash($src === 'wrong' ? 'Ainda não tem perguntas erradas para rever. Faça primeiro um treino.' : 'Não há perguntas disponíveis para esta selecção.', 'error');
    redirect('index.php');
}
$n = count($ids);
$limit = $mode === 'exame' ? $n * SECONDS_PER_QUESTION : 0;
$pdo->beginTransaction();
$pdo->prepare('INSERT INTO attempts (user_id, mode, source, total, time_limit, started_ts) VALUES (?,?,?,?,?,?)')
    ->execute([$uid, $mode, $src, $n, $limit, time()]);
$aid = (int)$pdo->lastInsertId();
$ins = $pdo->prepare('INSERT INTO attempt_answers (attempt_id, question_id, position) VALUES (?,?,?)');
foreach ($ids as $i => $qid) $ins->execute([$aid, (int)$qid, $i + 1]);
$pdo->commit();
redirect('quiz.php?id=' . $aid);
