<?php
/* Importa categorias e perguntas de data/questions.txt (formato: parte|pergunta|A|B|C|D|resposta|explicação) */
function seed_questions(PDO $pdo): int {
    $cats = [
        1 => ['Estatuto Orgânico do MININT', 'Decreto Presidencial n.º 32/18'],
        2 => ['Serviço Penitenciário', 'Decreto Presidencial n.º 184/17'],
        3 => ['Lei Penitenciária', 'Lei n.º 8/08, de 29 de Agosto'],
        4 => ['Trabalho prisional', 'Decreto n.º 64/04, de 1 de Outubro'],
        5 => ['Administração Pública e deontologia', 'Pauta Deontológica / Manual de Administração Pública'],
        6 => ['Cultura Geral, História e Organização do Estado', 'Pré-exames de Cultura Geral'],
        7 => ['Raciocínio (psicotécnica)', 'Métodos da prova psicotécnica do MININT'],
    ];
    $ins = $pdo->prepare('INSERT IGNORE INTO categories (id, name, source) VALUES (?,?,?)');
    foreach ($cats as $id => $c) $ins->execute([$id, $c[0], $c[1]]);

    if ((int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn() > 0) return 0;
    $lines = file(__DIR__ . '/../data/questions.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $st = $pdo->prepare('INSERT INTO questions (category_id, question, opt_a, opt_b, opt_c, opt_d, correct, explanation) VALUES (?,?,?,?,?,?,?,?)');
    $n = 0;
    $pdo->beginTransaction();
    foreach ($lines as $l) {
        $p = explode('|', $l);
        if (count($p) !== 8) continue;
        $st->execute([(int)$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7]]);
        $n++;
    }
    $pdo->commit();
    return $n;
}
