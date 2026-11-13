# Simulador de Prova Escrita — MININT

Sistema web (PHP + MySQL + JavaScript + CSS) para treinar a prova escrita do concurso do Ministério do Interior.
Inclui as **168 perguntas** do material "Prova Escrita de Treino", com resposta correcta e explicação.

## Funcionalidades
- Registo e login seguros (palavra-passe com hash, protecção CSRF, consultas preparadas)
- **Modo Treino**: resposta e explicação imediatas
- **Modo Exame**: cronómetro (60 s por pergunta, como na prova real) e correcção no fim; fecha sozinho quando o tempo acaba
- Escolha por matéria (Partes I a VII), número de perguntas, ou **só as perguntas que errou**
- Nota em 20 valores, desempenho por matéria, revisão completa com explicações
- Histórico de simulações e painel com estatísticas
- Área de administração: gerir perguntas (criar/editar/activar/eliminar), utilizadores e ver as perguntas com mais erros
- Correcção feita no servidor: a resposta certa nunca é enviada ao navegador antes de responder

## Requisitos
PHP 7.4 ou superior (extensão PDO MySQL) e MySQL/MariaDB. Funciona em XAMPP, WAMP, Laragon ou alojamento partilhado.

## Instalação (2 minutos)
1. Copie a pasta para `htdocs` (XAMPP) ou `www` (WAMP/Laragon), por exemplo `htdocs/minint`.
2. Abra `config.php` e ajuste `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (em XAMPP: utilizador `root`, palavra-passe vazia).
3. Abra no navegador `http://localhost/minint/install.php` e clique em **Instalar agora**.
   Isto cria a base de dados, as tabelas e importa as 168 perguntas.
4. Clique em **Criar conta**: o **primeiro utilizador registado é o administrador**.
5. Por segurança, apague o ficheiro `install.php` depois de instalar.

### Instalação alternativa (phpMyAdmin)
Importe `database/minint_completo.sql` (cria a base `minint_simulador` com tudo), depois ajuste o `config.php` e crie a conta em `register.php`. Não use esta via e o `install.php` em simultâneo.

## Estrutura
```
config.php          configuração e funções comuns
install.php         instalador
index.php           painel + iniciar simulação
start.php / quiz.php / api.php / result.php / history.php
login.php / register.php / logout.php
admin/              gestão de perguntas e utilizadores
assets/             css e js
data/questions.txt  banco de perguntas (parte|pergunta|A|B|C|D|resposta|explicação)
database/           schema.sql e minint_completo.sql
```

## Nota
As perguntas assentam nos textos do material carregado. Não há garantia de que coincidam com as da prova real; onde os manuais se contradizem (por exemplo, 18 ou 21 províncias), a explicação indica o diploma que prevalece.
