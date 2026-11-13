<?php
require_once __DIR__ . '/config.php';
if (user()) redirect('index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    if ($name === '' || mb_strlen($name) > 120) $err = 'Indique o seu nome.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Email inválido.';
    elseif (strlen($pass) < 6) $err = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    elseif ($pass !== ($_POST['password2'] ?? '')) $err = 'As palavras-passe não coincidem.';
    else {
        $pdo = db();
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) $err = 'Este email já está registado.';
        else {
            $isFirst = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
            $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $isFirst ? 'admin' : 'user']);
            $id = (int)$pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $isFirst ? 'admin' : 'user'];
            flash($isFirst ? 'Conta de administrador criada. Bem-vindo!' : 'Conta criada com sucesso. Bom estudo!', 'success');
            redirect('index.php');
        }
    }
}
$title = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<div class="narrow">
  <div class="card">
    <h1>Criar conta</h1>
    <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Nome completo<input type="text" name="name" required maxlength="120" value="<?= e($_POST['name'] ?? '') ?>"></label>
      <label>Email<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Palavra-passe (mín. 6)<input type="password" name="password" required minlength="6"></label>
      <label>Repetir palavra-passe<input type="password" name="password2" required minlength="6"></label>
      <button class="btn" type="submit">Criar conta</button>
    </form>
    <p class="muted">Já tem conta? <a href="<?= url('login.php') ?>">Entrar</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
