<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/seed.php';
$lock = __DIR__ . '/install.lock';
$msg = ''; $ok = false;
if (file_exists($lock)) {
    $msg = 'O sistema já está instalado. Por segurança, apague o ficheiro install.php.';
    $ok = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . str_replace('`', '', DB_NAME) . '`');
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) $pdo->exec($stmt);
        $n = seed_questions($pdo);
        file_put_contents($lock, date('c'));
        $ok = true;
        $msg = "Instalação concluída com sucesso. $n perguntas importadas. Crie agora a sua conta: o PRIMEIRO utilizador registado torna-se administrador.";
    } catch (Throwable $ex) {
        $msg = 'Erro: ' . $ex->getMessage() . ' — verifique as credenciais em config.php.';
    }
}
?><!DOCTYPE html>
<html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalação · <?= e(APP_NAME) ?></title><link rel="stylesheet" href="<?= url('assets/css/style.css') ?>"></head>
<body><main class="wrap narrow"><div class="card">
<h1>Instalação do <?= e(APP_NAME) ?></h1>
<?php if ($msg): ?><div class="alert <?= $ok ? 'success' : 'error' ?>"><?= e($msg) ?></div><?php endif; ?>
<?php if ($ok): ?>
  <p><a class="btn" href="<?= url('register.php') ?>">Criar conta de administrador</a></p>
<?php else: ?>
  <p>Base de dados: <b><?= e(DB_NAME) ?></b> em <b><?= e(DB_HOST) ?></b> (utilizador <b><?= e(DB_USER) ?></b>). Se estes dados não estiverem correctos, edite o ficheiro <code>config.php</code> primeiro.</p>
  <p>Este assistente cria a base de dados, as tabelas e importa as 168 perguntas do material.</p>
  <form method="post"><button class="btn" type="submit">Instalar agora</button></form>
<?php endif; ?>
</div></main></body></html>
