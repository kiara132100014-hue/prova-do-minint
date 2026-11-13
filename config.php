<?php
/* ===== CONFIGURAÇÃO — edite apenas as 4 linhas abaixo ===== */
define('DB_HOST', 'localhost');
define('DB_NAME', 'minint_simulador');
define('DB_USER', 'root');
define('DB_PASS', '');
/* ========================================================== */

define('APP_NAME', 'Simulador MININT');
define('SECONDS_PER_QUESTION', 60); // tempo por pergunta no modo Exame (igual à prova real)

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* URL base detectada automaticamente (funciona em subpastas) */
$__root = str_replace('\\', '/', (string)realpath(__DIR__));
$__doc  = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '/nonexistent'));
define('BASE_URL', ($__doc !== '' && strpos($__root, $__doc) === 0) ? rtrim(substr($__root, strlen($__doc)), '/') : '');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('Erro de ligação à base de dados. Verifique o config.php e execute install.php.');
        }
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }
function user(): ?array { return $_SESSION['user'] ?? null; }
function flash(string $msg, string $type = 'info'): void { $_SESSION['flash'][] = [$type, $msg]; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) {
        http_response_code(419);
        exit('Sessão expirada. Volte atrás e recarregue a página.');
    }
}

function require_login(): void { if (!user()) redirect('login.php'); }
function require_admin(): void {
    require_login();
    if ((user()['role'] ?? '') !== 'admin') { http_response_code(403); exit('Acesso negado.'); }
}

function grade20(float $pct): float { return round($pct / 100 * 20, 1); }

/* Fecha uma tentativa e calcula a nota */
function finish_attempt(int $id): void {
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM attempts WHERE id = ?');
    $st->execute([$id]);
    $a = $st->fetch();
    if (!$a || $a['finished_at'] !== null) return;
    $st = $pdo->prepare('SELECT COUNT(*) AS t, COALESCE(SUM(is_correct),0) AS c FROM attempt_answers WHERE attempt_id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    $total = (int)$r['t'];
    $correct = (int)$r['c'];
    $dur = time() - (int)$a['started_ts'];
    if ($a['mode'] === 'exame') $dur = min($dur, (int)$a['time_limit']);
    $score = $total > 0 ? round($correct * 100 / $total, 2) : 0;
    $pdo->prepare('UPDATE attempts SET total=?, correct=?, score=?, duration=?, finished_at=NOW() WHERE id=?')
        ->execute([$total, $correct, $score, max(0, $dur), $id]);
}

function fmt_time(int $s): string { return sprintf('%02d:%02d', intdiv($s, 60), $s % 60); }
