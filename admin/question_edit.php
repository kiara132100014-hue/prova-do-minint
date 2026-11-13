<?php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
$q = ['category_id' => 1, 'question' => '', 'opt_a' => '', 'opt_b' => '', 'opt_c' => '', 'opt_d' => '', 'correct' => 'A', 'explanation' => '', 'active' => 1];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM questions WHERE id = ?'); $st->execute([$id]);
    $q = $st->fetch();
    if (!$q) { flash('Pergunta não encontrada.', 'error'); redirect('admin/questions.php'); }
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'question' => trim($_POST['question'] ?? ''),
        'opt_a' => trim($_POST['opt_a'] ?? ''), 'opt_b' => trim($_POST['opt_b'] ?? ''),
        'opt_c' => trim($_POST['opt_c'] ?? ''), 'opt_d' => trim($_POST['opt_d'] ?? ''),
        'correct' => strtoupper($_POST['correct'] ?? ''),
        'explanation' => trim($_POST['explanation'] ?? ''),
        'active' => isset($_POST['active']) ? 1 : 0,
    ];
    $catOk = in_array($d['category_id'], array_map('intval', array_column($cats, 'id')), true);
    if (!$catOk || $d['question'] === '' || $d['opt_a'] === '' || $d['opt_b'] === '' || $d['opt_c'] === '' || $d['opt_d'] === ''
        || !in_array($d['correct'], ['A', 'B', 'C', 'D'], true) || $d['explanation'] === '') {
        $err = 'Preencha todos os campos e escolha a resposta correcta.';
        $q = array_merge($q, $d);
    } else {
        if ($id) {
            $pdo->prepare('UPDATE questions SET category_id=?, question=?, opt_a=?, opt_b=?, opt_c=?, opt_d=?, correct=?, explanation=?, active=? WHERE id=?')
                ->execute([$d['category_id'], $d['question'], $d['opt_a'], $d['opt_b'], $d['opt_c'], $d['opt_d'], $d['correct'], $d['explanation'], $d['active'], $id]);
        } else {
            $pdo->prepare('INSERT INTO questions (category_id, question, opt_a, opt_b, opt_c, opt_d, correct, explanation, active) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$d['category_id'], $d['question'], $d['opt_a'], $d['opt_b'], $d['opt_c'], $d['opt_d'], $d['correct'], $d['explanation'], $d['active']]);
        }
        flash('Pergunta guardada.', 'success');
        redirect('admin/questions.php');
    }
}
$title = $id ? 'Editar pergunta' : 'Nova pergunta';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= e($title) ?></h1>
<?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="card form">
  <?= csrf_field() ?>
  <label>Matéria<select name="category_id"><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$q['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>Parte <?= (int)$c['id'] ?> — <?= e($c['name']) ?></option><?php endforeach; ?></select></label>
  <label>Pergunta<textarea name="question" rows="3" required><?= e($q['question']) ?></textarea></label>
  <?php foreach (['a', 'b', 'c', 'd'] as $L): ?>
    <label>Opção <?= strtoupper($L) ?><input type="text" name="opt_<?= $L ?>" maxlength="500" required value="<?= e($q['opt_' . $L]) ?>"></label>
  <?php endforeach; ?>
  <label>Resposta correcta<select name="correct"><?php foreach (['A', 'B', 'C', 'D'] as $L): ?><option <?= $q['correct'] === $L ? 'selected' : '' ?>><?= $L ?></option><?php endforeach; ?></select></label>
  <label>Explicação<textarea name="explanation" rows="4" required><?= e($q['explanation']) ?></textarea></label>
  <label class="check"><input type="checkbox" name="active" <?= $q['active'] ? 'checked' : '' ?>> Pergunta activa</label>
  <div><button class="btn" type="submit">Guardar</button> <a class="btn ghost" href="questions.php">Cancelar</a></div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
