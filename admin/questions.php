<?php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $qid = (int)($_POST['qid'] ?? 0);
    if (($_POST['act'] ?? '') === 'delete') { $pdo->prepare('DELETE FROM questions WHERE id=?')->execute([$qid]); flash('Pergunta eliminada.', 'success'); }
    if (($_POST['act'] ?? '') === 'toggle') { $pdo->prepare('UPDATE questions SET active = 1 - active WHERE id=?')->execute([$qid]); flash('Estado alterado.', 'success'); }
    header('Location: ' . $_SERVER['REQUEST_URI']); exit;
}
$cat = (int)($_GET['cat'] ?? 0);
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per = 25;
$where = ' WHERE 1=1'; $par = [];
if ($cat) { $where .= ' AND q.category_id = ?'; $par[] = $cat; }
if ($q !== '') { $where .= ' AND q.question LIKE ?'; $par[] = '%' . $q . '%'; }
$st = $pdo->prepare('SELECT COUNT(*) FROM questions q' . $where); $st->execute($par);
$total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$off = ($page - 1) * $per;
$st = $pdo->prepare('SELECT q.*, c.name cname FROM questions q JOIN categories c ON c.id = q.category_id' . $where . " ORDER BY q.id DESC LIMIT $per OFFSET $off");
$st->execute($par);
$rows = $st->fetchAll();
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
$qs = http_build_query(['cat' => $cat ?: '', 'q' => $q]);
$title = 'Perguntas';
require __DIR__ . '/../includes/header.php';
?>
<h1>Perguntas <span class="muted">(<?= $total ?>)</span></h1>
<form method="get" class="filters">
  <select name="cat"><option value="0">Todas as matérias</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>>Parte <?= (int)$c['id'] ?> — <?= e($c['name']) ?></option><?php endforeach; ?></select>
  <input type="search" name="q" placeholder="Pesquisar texto…" value="<?= e($q) ?>">
  <button class="btn" type="submit">Filtrar</button>
  <a class="btn ghost" href="question_edit.php">+ Nova pergunta</a>
</form>
<div class="card"><div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>#</th><th>Parte</th><th>Pergunta</th><th>Certa</th><th>Estado</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['active'] ? '' : 'off' ?>">
      <td><?= (int)$r['id'] ?></td><td><?= (int)$r['category_id'] ?></td>
      <td><?= e(mb_strimwidth($r['question'], 0, 120, '…')) ?></td><td><b><?= e($r['correct']) ?></b></td>
      <td><?= $r['active'] ? 'Activa' : 'Inactiva' ?></td>
      <td class="acts">
        <a href="question_edit.php?id=<?= (int)$r['id'] ?>">Editar</a>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="qid" value="<?= (int)$r['id'] ?>"><button class="lnk" name="act" value="toggle">Activar/Desactivar</button></form>
        <form method="post" class="inline" onsubmit="return confirm('Eliminar esta pergunta e as respostas associadas?')"><?= csrf_field() ?><input type="hidden" name="qid" value="<?= (int)$r['id'] ?>"><button class="lnk red" name="act" value="delete">Eliminar</button></form>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div></div>
<div class="pager">
  <?php if ($page > 1): ?><a class="btn ghost" href="?<?= e($qs) ?>&p=<?= $page - 1 ?>">← Anterior</a><?php endif; ?>
  <span class="muted">Página <?= $page ?> de <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn ghost" href="?<?= e($qs) ?>&p=<?= $page + 1 ?>">Seguinte →</a><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
