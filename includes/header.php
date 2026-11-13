<?php
require_once __DIR__ . '/../config.php';
$u = user();
$cur = basename($_SERVER['SCRIPT_NAME']);
$inAdmin = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? 'Início') . ' · ' . APP_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="topbar">
  <div class="wrap bar">
    <a class="brand" href="<?= url($u ? 'index.php' : 'login.php') ?>"><span class="logo">★</span><span>Simulador <b>MININT</b></span></a>
    <nav>
      <?php if ($u): ?>
        <a class="<?= ($cur === 'index.php' && !$inAdmin) ? 'on' : '' ?>" href="<?= url('index.php') ?>">Painel</a>
        <a class="<?= $cur === 'history.php' ? 'on' : '' ?>" href="<?= url('history.php') ?>">Histórico</a>
        <?php if ($u['role'] === 'admin'): ?><a class="<?= $inAdmin ? 'on' : '' ?>" href="<?= url('admin/index.php') ?>">Administração</a><?php endif; ?>
        <span class="who"><?= e($u['name']) ?></span>
        <a class="btn-out" href="<?= url('logout.php') ?>">Sair</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Entrar</a>
        <a href="<?= url('register.php') ?>">Criar conta</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap">
<?php if (!empty($_SESSION['flash'])): foreach ($_SESSION['flash'] as $f): ?>
  <div class="alert <?= e($f[0]) ?>"><?= e($f[1]) ?></div>
<?php endforeach; unset($_SESSION['flash']); endif; ?>
