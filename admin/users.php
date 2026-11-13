<?php
require_once __DIR__ . '/../config.php';
require_admin();
$pdo = db();
$me = user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid = (int)($_POST['uid'] ?? 0);
    if ($uid === $me) { flash('Não pode alterar a sua própria conta aqui.', 'error'); }
    elseif (($_POST['act'] ?? '') === 'role') {
        $pdo->prepare("UPDATE users SET role = IF(role='admin','user','admin') WHERE id=?")->execute([$uid]);
        flash('Perfil alterado.', 'success');
    } elseif (($_POST['act'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
        flash('Utilizador eliminado.', 'success');
    }
    redirect('admin/users.php');
}
$rows = $pdo->query('SELECT u.*, (SELECT COUNT(*) FROM attempts a WHERE a.user_id=u.id AND a.finished_at IS NOT NULL) AS n, (SELECT ROUND(AVG(score),1) FROM attempts a WHERE a.user_id=u.id AND a.finished_at IS NOT NULL) AS avg FROM users u ORDER BY u.id')->fetchAll();
$title = 'Utilizadores';
require __DIR__ . '/../includes/header.php';
?>
<h1>Utilizadores</h1>
<div class="card"><div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>#</th><th>Nome</th><th>Email</th><th>Perfil</th><th>Simulações</th><th>Média %</th><th>Registo</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td>
      <td><?= $r['role'] === 'admin' ? 'Administrador' : 'Utilizador' ?></td>
      <td><?= (int)$r['n'] ?></td><td><?= $r['avg'] !== null ? e($r['avg']) : '—' ?></td><td><?= e(substr($r['created_at'], 0, 10)) ?></td>
      <td class="acts"><?php if ((int)$r['id'] !== $me): ?>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="uid" value="<?= (int)$r['id'] ?>"><button class="lnk" name="act" value="role">Alternar admin</button></form>
        <form method="post" class="inline" onsubmit="return confirm('Eliminar este utilizador e todo o seu histórico?')"><?= csrf_field() ?><input type="hidden" name="uid" value="<?= (int)$r['id'] ?>"><button class="lnk red" name="act" value="delete">Eliminar</button></form>
      <?php else: ?><span class="muted">(você)</span><?php endif; ?></td>
    </tr>
  <?php endforeach; ?></tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
