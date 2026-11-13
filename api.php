<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

function out($data, int $code = 200): void { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

if (!user()) out(['error' => 'Sessão expirada. Inicie sessão novamente.'], 401);
$in = json_decode(file_get_contents('php://input') ?: '', true) ?: [];
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) csrf_check();
$action = $_GET['action'] ?? ($in['action'] ?? '');
$id = (int)($_GET['id'] ?? ($in['id'] ?? 0));

$pdo = db();
$st = $pdo->prepare('SELECT * FROM attempts WHERE id = ? AND user_id = ?');
$st->execute([$id, user()['id']]);
$a = $st->fetch();
if (!$a) out(['error' => 'Simulação não encontrada.'], 404);

$left = null;
if ($a['mode'] === 'exame') $left = max(0, (int)$a['time_limit'] - (time() - (int)$a['started_ts']));
$finished = $a['finished_at'] !== null;

if ($action === 'load') {
    if ($finished) out(['finished' => true]);
    if ($left === 0) { finish_attempt($id); out(['finished' => true]); }
    $st = $pdo->prepare('SELECT aa.question_id, aa.position, aa.chosen, aa.is_correct, q.question, q.opt_a, q.opt_b, q.opt_c, q.opt_d, q.correct, q.explanation, c.name AS cat
        FROM attempt_answers aa JOIN questions q ON q.id = aa.question_id JOIN categories c ON c.id = q.category_id
        WHERE aa.attempt_id = ? ORDER BY aa.position');
    $st->execute([$id]);
    $qs = [];
    foreach ($st->fetchAll() as $r) {
        $q = [
            'qid' => (int)$r['question_id'], 'pos' => (int)$r['position'], 'cat' => $r['cat'], 'text' => $r['question'],
            'options' => ['A' => $r['opt_a'], 'B' => $r['opt_b'], 'C' => $r['opt_c'], 'D' => $r['opt_d']],
            'chosen' => $r['chosen'],
        ];
        if ($a['mode'] === 'treino' && $r['chosen'] !== null) {
            $q['correct'] = $r['correct'];
            $q['explanation'] = $r['explanation'];
        }
        $qs[] = $q;
    }
    out(['mode' => $a['mode'], 'left' => $left, 'questions' => $qs]);
}

if (!$isPost) out(['error' => 'Pedido inválido.'], 400);

if ($action === 'answer') {
    if ($finished) out(['error' => 'Esta simulação já terminou.', 'finished' => true], 409);
    if ($left === 0) { finish_attempt($id); out(['error' => 'Tempo esgotado.', 'finished' => true], 409); }
    $qid = (int)($in['qid'] ?? 0);
    $choice = strtoupper((string)($in['choice'] ?? ''));
    if (!in_array($choice, ['A', 'B', 'C', 'D'], true)) out(['error' => 'Opção inválida.'], 400);
    $st = $pdo->prepare('SELECT aa.id, aa.chosen, q.correct, q.explanation FROM attempt_answers aa JOIN questions q ON q.id = aa.question_id WHERE aa.attempt_id = ? AND aa.question_id = ?');
    $st->execute([$id, $qid]);
    $r = $st->fetch();
    if (!$r) out(['error' => 'Pergunta inválida.'], 404);
    if ($a['mode'] === 'treino') {
        if ($r['chosen'] === null) {
            $pdo->prepare('UPDATE attempt_answers SET chosen = ?, is_correct = ? WHERE id = ?')
                ->execute([$choice, $choice === $r['correct'] ? 1 : 0, $r['id']]);
            $r['chosen'] = $choice;
        }
        out(['ok' => true, 'chosen' => $r['chosen'], 'correct' => $r['correct'], 'explanation' => $r['explanation']]);
    }
    $pdo->prepare('UPDATE attempt_answers SET chosen = ?, is_correct = ? WHERE id = ?')
        ->execute([$choice, $choice === $r['correct'] ? 1 : 0, $r['id']]);
    out(['ok' => true, 'left' => $left]);
}

if ($action === 'finish') {
    finish_attempt($id);
    out(['ok' => true, 'url' => url('result.php?id=' . $id)]);
}

out(['error' => 'Acção desconhecida.'], 400);
