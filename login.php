<?php
require_once __DIR__ . '/config.php';
if (user()) redirect('index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
        redirect('index.php');
    }
    usleep(400000);
    $err = 'Email ou palavra-passe incorrectos.';
}
$title = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<div class="narrow">
  <div class="card">
    <h1>Entrar</h1>
    <p class="muted">Aceda ao simulador da prova escrita do MININT.</p>
    <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Email<input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Palavra-passe<input type="password" name="password" required></label>
      <button class="btn" type="submit">Entrar</button>
    </form>
    <p class="muted">Ainda não tem conta? <a href="<?= url('register.php') ?>">Criar conta</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
