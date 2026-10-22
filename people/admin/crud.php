<?php
/* WWAnalog — CRUD administrativo PostgreSQL: cria, consulta, atualiza e exclui registros permitidos. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../../" porque este arquivo está dentro da subpasta people/admin/.
require_once __DIR__ . '/../../includes/functions.php';

// Exige que o usuário logado seja ADMINISTRADOR. Se não for, bloqueia o acesso.
require_admin();

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário.
check_csrf();

// Define o título da página e marca "admin" como aba ativa do menu.
$page_title = 'CRUD PostgreSQL';
$active = 'admin';

// ===== LISTA DE ENTIDADES PERMITIDAS =====
// Isso é uma "allowlist": só aceita estes tipos de registros.
// Impede que alguém tente acessar uma tabela qualquer digitando o nome na URL.
$allowed = ['works', 'categories', 'tags', 'episodes', 'comments', 'users', 'contact_requests'];

// Pega a entidade escolhida (pela URL ou pelo formulário). Se não vier nada, usa "works".
// Se a entidade escolhida NÃO estiver na lista permitida, volta para "works".
$entity = (string) ($_GET['entity'] ?? $_POST['entity'] ?? 'works');
if (!in_array($entity, $allowed, true))
    $entity = 'works';

// Prepara variáveis que serão usadas ao longo do script.
$pdo = null;        // Conexão com o banco
$error = '';        // Mensagem de erro (se houver)
$notice = '';       // Mensagem de sucesso (se houver)
$editRow = null;    // Registro sendo editado (se houver)
$rows = [];         // Lista de registros para exibir na tabela
$categories = [];   // Lista de categorias (para preencher o menu)
$works = [];        // Lista de obras (para preencher o menu)
$roles = [];        // Lista de papéis de usuário (para preencher o menu)

// Rótulos amigáveis de cada entidade (usados no título e nos botões de navegação).
$entityLabels = [
    'works'            => 'Obras',
    'categories'       => 'Categorias',
    'tags'             => 'Tags',
    'episodes'         => 'Episódios',
    'comments'         => 'Comentários',
    'users'            => 'Usuários',
    'contact_requests' => 'Solicitações de contato'
];

// Função auxiliar que escapa texto para evitar XSS.
// Sempre que formos imprimir algo vindo do banco ou do usuário, passamos por aqui.
function crud_h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// Função auxiliar que trata um campo opcional:
// se vier vazio, vira NULL (para o banco entender "sem valor").
function crud_nullable(string $key): ?string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    return $v === '' ? null : $v;
}

// ===== BLOCO PRINCIPAL COM TRATAMENTO DE ERROS =====
try {
    // Abre a conexão com o PostgreSQL.
    $pdo = db();

    // Carrega listas auxiliares para preencher os menus dos formulários.
    $categories = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
    $tags       = $pdo->query('SELECT id,name FROM tags ORDER BY name')->fetchAll();
    $works      = $pdo->query('SELECT id,title FROM works ORDER BY title')->fetchAll();
    $roles      = $pdo->query('SELECT id,name FROM roles ORDER BY name')->fetchAll();
    $users      = $pdo->query('SELECT id,nickname FROM users ORDER BY nickname')->fetchAll();

    // ===== PROCESSAMENTO DE FORMULÁRIOS (SÓ SE ENVIOU ALGO) =====
    // Toda escrita (criar, editar, excluir) só acontece depois de:
    //   1) o admin estar autorizado (require_admin no topo)
    //   2) o CSRF ter sido validado (check_csrf no topo)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Pega qual ação o admin quer fazer: "save" (salvar) ou "delete" (excluir).
        $action = (string) ($_POST['action'] ?? '');

        // Pega o ID do registro (se for edição/exclusão). "filter_var" valida se é número inteiro.
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

        // ===== AÇÃO DE EXCLUIR =====
        if ($action === 'delete') {

            // Se o ID for inválido, lança erro e para.
            if (!$id)
                throw new RuntimeException('Registro inválido para exclusão.');

            // Dependendo da entidade, faz a exclusão de forma específica.
            switch ($entity) {

                // Obras: apaga primeiro todos os vínculos (categorias, tags, comentários,
                // avaliações, listas, episódios) e só depois a obra em si.
                // Tudo dentro de uma transação: se algo falhar, nada é apagado.
                case 'works':
                    $pdo->beginTransaction();
                    $pdo->prepare('DELETE FROM work_categories WHERE work_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM work_tags WHERE work_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM comments WHERE work_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM ratings WHERE work_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM user_lists WHERE work_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM episodes WHERE work_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM works WHERE id=?')->execute([$id]);
                    $pdo->commit();
                    break;

                // Categorias: apaga os vínculos com obras e depois a categoria.
                case 'categories':
                    $pdo->prepare('DELETE FROM work_categories WHERE category_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
                    break;

                // Tags: apaga os vínculos com obras e depois a tag.
                case 'tags':
                    $pdo->prepare('DELETE FROM work_tags WHERE tag_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM tags WHERE id=?')->execute([$id]);
                    break;

                // Episódios: apaga o episódio.
                case 'episodes':
                    $pdo->prepare('DELETE FROM episodes WHERE id=?')->execute([$id]);
                    break;

                // Comentários: apaga o comentário.
                case 'comments':
                    $pdo->prepare('DELETE FROM comments WHERE id=?')->execute([$id]);
                    break;

                // Usuários: NÃO permite excluir (para preservar histórico).
                case 'users':
                    throw new RuntimeException('A exclusão de usuários foi bloqueada para preservar avaliações, comentários e listas. Altere o status para suspenso/inativo.');

                // Solicitações de contato: apaga a solicitação.
                case 'contact_requests':
                    $pdo->prepare('DELETE FROM contact_requests WHERE id=?')->execute([$id]);
                    break;
            }

            // Registra no histórico de auditoria.
            audit_event('postgres_crud_delete', $entity . ' id=' . $id);

            // Prepara mensagem de sucesso.
            $notice = 'Registro excluído do PostgreSQL.';

        // ===== AÇÃO DE SALVAR (CRIAR OU EDITAR) =====
        } elseif ($action === 'save') {

            // Dependendo da entidade, valida os campos e faz INSERT ou UPDATE.
            switch ($entity) {

                // ---- OBRAS ----
                case 'works':
                    $title = trim((string) ($_POST['title'] ?? ''));
                    $slug  = trim((string) ($_POST['slug'] ?? ''));

                    // Validação: título obrigatório e slug no formato correto (minúsculas, números, hífens).
                    if ($title === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug))
                        throw new RuntimeException('Título obrigatório e slug em minúsculas, números e hífens.');

                    // Monta os dados que serão salvos.
                    $data = [
                        $title,
                        $slug,
                        crud_nullable('description'),
                        crud_nullable('creator'),
                        ($_POST['release_year'] ?? '') !== '' ? (int) $_POST['release_year'] : null,
                        crud_nullable('status'),
                        crud_nullable('classification'),
                        crud_nullable('content_warnings')
                    ];

                    // Se tem ID, é edição (UPDATE). Senão, é criação (INSERT).
                    if ($id) {
                        $data[] = $id;
                        $pdo->prepare('UPDATE works SET title=?,slug=?,description=?,creator=?,release_year=?,status=?,classification=?,content_warnings=? WHERE id=?')->execute($data);
                        $workId = $id;
                    } else {
                        $st = $pdo->prepare('INSERT INTO works (title,slug,description,creator,release_year,status,classification,content_warnings) VALUES (?,?,?,?,?,?,?,?) RETURNING id');
                        $st->execute($data);
                        $workId = (int) $st->fetchColumn();  // Pega o ID recém-criado
                    }

                    // Atualiza a categoria principal da obra (apaga a antiga e insere a nova).
                    $catId = (int) ($_POST['category_id'] ?? 0);
                    $pdo->prepare('DELETE FROM work_categories WHERE work_id=?')->execute([$workId]);
                    if ($catId) {
                        $pdo->prepare('INSERT INTO work_categories (work_id,category_id) VALUES (?,?) ON CONFLICT DO NOTHING')->execute([$workId, $catId]);
                    }

                    // Atualiza as tags da obra (apaga todas e insere as escolhidas).
                    $tagIds = array_values(array_unique(array_filter(
                        array_map('intval', (array) ($_POST['tag_ids'] ?? [])),
                        fn($v) => $v > 0
                    )));
                    $pdo->prepare('DELETE FROM work_tags WHERE work_id=?')->execute([$workId]);
                    if ($tagIds) {
                        $tagInsert = $pdo->prepare('INSERT INTO work_tags(work_id,tag_id) VALUES(?,?) ON CONFLICT DO NOTHING');
                        foreach ($tagIds as $tagId)
                            $tagInsert->execute([$workId, $tagId]);
                    }
                    break;

                // ---- CATEGORIAS ----
                case 'categories':
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $slug = trim((string) ($_POST['slug'] ?? ''));

                    // Validação: nome obrigatório e slug no formato correto.
                    if ($name === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug))
                        throw new RuntimeException('Nome obrigatório e slug válido.');

                    $vals = [$name, $slug, crud_nullable('description')];

                    // Se tem ID, edita. Senão, cria.
                    if ($id) {
                        $vals[] = $id;
                        $pdo->prepare('UPDATE categories SET name=?,slug=?,description=? WHERE id=?')->execute($vals);
                    } else {
                        $pdo->prepare('INSERT INTO categories(name,slug,description) VALUES(?,?,?)')->execute($vals);
                    }
                    break;

                // ---- TAGS ----
                case 'tags':
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $slug = trim((string) ($_POST['slug'] ?? ''));

                    // Validação: nome obrigatório e slug no formato correto.
                    if ($name === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug))
                        throw new RuntimeException('Nome obrigatório e slug válido.');

                    if ($id)
                        $pdo->prepare('UPDATE tags SET name=?,slug=? WHERE id=?')->execute([$name, $slug, $id]);
                    else
                        $pdo->prepare('INSERT INTO tags(name,slug) VALUES(?,?)')->execute([$name, $slug]);
                    break;

                // ---- EPISÓDIOS ----
                case 'episodes':
                    $workId = (int) ($_POST['work_id'] ?? 0);
                    $title  = trim((string) ($_POST['title'] ?? ''));

                    // Validação: precisa escolher a obra e informar o título do episódio.
                    if (!$workId || $title === '')
                        throw new RuntimeException('Selecione a obra e informe o título do episódio.');

                    $num  = ($_POST['episode_number'] ?? '') !== '' ? (int) $_POST['episode_number'] : null;
                    $date = crud_nullable('release_date');
                    $url  = crud_nullable('video_url');
                    $desc = crud_nullable('description');

                    if ($id)
                        $pdo->prepare('UPDATE episodes SET work_id=?,title=?,episode_number=?,release_date=?,video_url=?,description=? WHERE id=?')->execute([$workId, $title, $num, $date, $url, $desc, $id]);
                    else
                        $pdo->prepare('INSERT INTO episodes(work_id,title,episode_number,release_date,video_url,description) VALUES(?,?,?,?,?,?)')->execute([$workId, $title, $num, $date, $url, $desc]);
                    break;

                // ---- COMENTÁRIOS ----
                case 'comments':
                    $status = trim((string) ($_POST['status'] ?? 'published'));

                    // Validação: status só aceita valores previstos.
                    if (!in_array($status, ['published', 'pending', 'flagged', 'removed', 'publicado', 'oculto', 'removido'], true))
                        throw new RuntimeException('Situação de comentário inválida.');

                    $content = trim((string) ($_POST['content'] ?? ''));
                    $userId  = (int) ($_POST['user_id'] ?? 0);
                    $workId  = (int) ($_POST['work_id'] ?? 0);

                    // Validação: comentário não pode ser vazio.
                    if ($content === '')
                        throw new RuntimeException('O comentário não pode ficar vazio.');

                    if ($id)
                        $pdo->prepare('UPDATE comments SET content=?,status=? WHERE id=?')->execute([$content, $status, $id]);
                    else {
                        // Para criar, precisa escolher usuário e obra.
                        if (!$userId || !$workId)
                            throw new RuntimeException('Selecione o usuário e a obra.');
                        $pdo->prepare('INSERT INTO comments(user_id,work_id,content,status) VALUES(?,?,?,?)')->execute([$userId, $workId, $content, $status]);
                    }
                    break;

                // ---- USUÁRIOS ----
                case 'users':
                    $status = trim((string) ($_POST['status'] ?? 'ativo'));
                    $roleId = (int) ($_POST['role_id'] ?? 0);

                    // Validação: status e papel válidos.
                    if (!in_array($status, ['ativo', 'suspenso', 'inativo', 'active', 'suspended', 'inactive'], true) || !$roleId)
                        throw new RuntimeException('Selecione um status e papel válidos.');

                    if ($id) {
                        // Edição: só altera status e papel (NÃO mexe na senha).
                        $pdo->prepare('UPDATE users SET status=?,role_id=? WHERE id=?')->execute([$status, $roleId, $id]);
                    } else {
                        // Criação: precisa de nome, apelido, e-mail válido e senha com 8+ caracteres.
                        $name     = trim((string) ($_POST['name'] ?? ''));
                        $nickname = trim((string) ($_POST['nickname'] ?? ''));
                        $email    = trim((string) ($_POST['email'] ?? ''));
                        $password = (string) ($_POST['password'] ?? '');

                        if ($name === '' || $nickname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8)
                            throw new RuntimeException('Informe nome, apelido, e-mail válido e senha com pelo menos 8 caracteres.');

                        // Criptografa a senha antes de salvar (nunca guardamos a senha "pura").
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare('INSERT INTO users(name,nickname,email,password_hash,role_id,status) VALUES(?,?,?,?,?,?)')->execute([$name, $nickname, $email, $hash, $roleId, $status]);
                    }
                    break;

                // ---- SOLICITAÇÕES DE CONTATO ----
                case 'contact_requests':
                    $status = trim((string) ($_POST['status'] ?? 'pendente'));

                    // Validação: status válido.
                    if (!in_array($status, ['pendente', 'em_analise', 'resolvida', 'rejeitada', 'pending', 'in_review', 'resolved', 'rejected'], true))
                        throw new RuntimeException('Situação de solicitação inválida.');

                    if ($id) {
                        // Edição: só altera o status.
                        $pdo->prepare('UPDATE contact_requests SET status=? WHERE id=?')->execute([$status, $id]);
                    } else {
                        // Criação: precisa de assunto e mensagem; tipo válido.
                        $name    = crud_nullable('name');
                        $email   = crud_nullable('email');
                        $type    = trim((string) ($_POST['request_type'] ?? 'outro'));
                        $subject = trim((string) ($_POST['subject'] ?? ''));
                        $message = trim((string) ($_POST['message'] ?? ''));

                        if ($subject === '' || $message === '')
                            throw new RuntimeException('Assunto e mensagem são obrigatórios.');

                        if (!in_array($type, ['correcao', 'atualizacao', 'remocao', 'reclamacao', 'sugestao', 'outro'], true))
                            throw new RuntimeException('Tipo de solicitação inválido.');

                        $pdo->prepare('INSERT INTO contact_requests(name,email,request_type,subject,message,status) VALUES(?,?,?,?,?,?)')->execute([$name, $email, $type, $subject, $message, $status]);
                    }
                    break;
            }

            // Registra no histórico de auditoria.
            audit_event('postgres_crud_save', $entity . ' id=' . ($id ?: 'novo'));

            // Prepara mensagem de sucesso.
            $notice = 'Alterações gravadas no PostgreSQL.';
        }
    }

    // ===== CARREGAR REGISTRO PARA EDIÇÃO (SE CLICOU EM "EDITAR") =====
    // Se a URL tem ?edit=123, busca esse registro para preencher o formulário.
    if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
        $id = (int) $_GET['edit'];

        // Descobre qual tabela consultar conforme a entidade.
        $table = [
            'works'            => 'works',
            'categories'       => 'categories',
            'tags'             => 'tags',
            'episodes'         => 'episodes',
            'comments'         => 'comments',
            'users'            => 'users',
            'contact_requests' => 'contact_requests'
        ][$entity];

        // Busca o registro no banco.
        $st = $pdo->prepare("SELECT * FROM {$table} WHERE id=?");
        $st->execute([$id]);
        $editRow = $st->fetch() ?: null;

        // Se não encontrou, avisa.
        if (!$editRow)
            $error = 'Registro não encontrado.';

        // Se for obra, busca também a categoria e as tags vinculadas.
        if ($entity === 'works' && $editRow) {
            $st = $pdo->prepare('SELECT category_id FROM work_categories WHERE work_id=? LIMIT 1');
            $st->execute([$id]);
            $editRow['category_id'] = $st->fetchColumn() ?: '';

            $st = $pdo->prepare('SELECT tag_id FROM work_tags WHERE work_id=?');
            $st->execute([$id]);
            $editRow['tag_ids'] = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        }
    }

    // ===== LISTAGEM COM BUSCA (SELECT) =====
    // Para cada entidade, define uma consulta SQL com filtro de busca.
    // O "ILIKE" faz busca sem diferenciar maiúsculas/minúsculas.
    // O "LIMIT 200" evita carregar registros demais.
    $q = trim((string) ($_GET['q'] ?? ''));

    $queries = [
        'works' => ['SELECT w.id,w.title,w.slug,w.creator,w.release_year,w.status,w.classification,c.name AS category FROM works w LEFT JOIN work_categories wc ON wc.work_id=w.id LEFT JOIN categories c ON c.id=wc.category_id WHERE (? = \'\' OR w.title ILIKE ? OR w.slug ILIKE ? OR COALESCE(w.creator,\'\') ILIKE ?) ORDER BY w.title LIMIT 200', fn($q) => [$q, '%' . $q . '%', '%' . $q . '%', '%' . $q . '%']],
        'categories' => ['SELECT id,name,slug,description FROM categories WHERE (? = \'\' OR name ILIKE ? OR slug ILIKE ?) ORDER BY name LIMIT 200', fn($q) => [$q, '%' . $q . '%', '%' . $q . '%']],
        'tags' => ['SELECT id,name,slug FROM tags WHERE (? = \'\' OR name ILIKE ? OR slug ILIKE ?) ORDER BY name LIMIT 200', fn($q) => [$q, '%' . $q . '%', '%' . $q . '%']],
        'episodes' => ['SELECT e.id,e.title,e.episode_number,e.release_date,e.video_url,w.title AS work_title FROM episodes e JOIN works w ON w.id=e.work_id WHERE (? = \'\' OR e.title ILIKE ? OR w.title ILIKE ?) ORDER BY w.title,e.episode_number NULLS LAST LIMIT 200', fn($q) => [$q, '%' . $q . '%', '%' . $q . '%']],
        'comments' => ['SELECT c.id,c.user_id,c.work_id,c.content,c.status,c.created_at,u.nickname,w.title AS work_title FROM comments c JOIN users u ON u.id=c.user_id JOIN works w ON w.id=c.work_id WHERE (? = \'\' OR c.content ILIKE ? OR u.nickname ILIKE ? OR w.title ILIKE ?) ORDER BY c.created_at DESC LIMIT 200', fn($q) => [$q, '%' . $q . '%', '%' . $q . '%', '%' . $q . '%']],
        'users' => ['SELECT u.id,u.name,u.nickname,u.email,u.status,u.role_id,r.name AS role FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE (? = \'\' OR u.name ILIKE ? OR u.nickname ILIKE ? OR u.email ILIKE ?) ORDER BY u.id DESC LIMIT 200', fn($q) => [$q, '%' . $q . '%', '%' . $q . '%', '%' . $q . '%']],
        'contact_requests' => ['SELECT id,name,email,request_type,subject,status,created_at FROM contact_requests WHERE (? = \'\' OR COALESCE(name,\'\') ILIKE ? OR COALESCE(email,\'\') ILIKE ? OR subject ILIKE ?) ORDER BY created_at DESC LIMIT 200', fn($q) => [$q, '%' . $q . '%', '%' . $q . '%', '%' . $q . '%']]
    ];

    // Pega a consulta correspondente à entidade atual e executa.
    [$sql, $paramFn] = $queries[$entity];
    $st = $pdo->prepare($sql);
    $st->execute($paramFn($q));
    $rows = $st->fetchAll();

} catch (Throwable $e) {
    // Se algo der errado, desfaz qualquer transação aberta.
    if ($pdo instanceof PDO && $pdo->inTransaction())
        $pdo->rollBack();

    // Prepara a mensagem de erro amigável.
    $error = $e instanceof PDOException
        ? 'Erro no PostgreSQL. Confira se o esquema das tabelas corresponde ao guia do projeto e se a conexão está configurada.'
        : $e->getMessage();
}

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/../../includes/header.php';
?>

<!-- ===== CABEÇALHO DA PÁGINA ===== -->
<div class="section-head">
    <div>
        <div class="eyebrow">PAINEL ADMINISTRATIVO · POSTGRESQL</div>
        <!-- Mostra o título com o nome amigável da entidade (ex.: "CRUD — Obras") -->
        <h1>CRUD — <?= crud_h($entityLabels[$entity]) ?></h1>
        <p class="small muted">Cadastrar, consultar, editar e excluir quando aplicável. As alterações desta tela são
            executadas no banco PostgreSQL.</p>
    </div>
    <!-- Botão para voltar ao painel principal -->
    <a class="btn ghost" href="index.php">Painel</a>
</div>

<!-- Mostra mensagem de erro (se houver) -->
<?php if ($error): ?>
    <div class="notice" role="alert"><?= crud_h($error) ?></div>
<?php endif; ?>

<!-- Mostra mensagem de sucesso (se houver) -->
<?php if ($notice): ?>
    <div class="notice success" role="status"><?= crud_h($notice) ?></div>
<?php endif; ?>

<!-- ===== BOTÕES PARA TROCAR DE ENTIDADE ===== -->
<!-- Cada botão leva para ?entity=xxx. O botão da entidade atual fica destacado. -->
<div class="hero-actions" style="margin-bottom:18px;flex-wrap:wrap">
    <?php foreach ($entityLabels as $key => $label): ?>
        <a class="btn <?= $entity === $key ? '' : 'ghost' ?>" href="crud.php?entity=<?= crud_h($key) ?>"><?= crud_h($label) ?></a>
    <?php endforeach; ?>
</div>

<!-- ===== FORMULÁRIO DE CADASTRO/EDIÇÃO (obras, categorias, tags, episódios) ===== -->
<?php if (in_array($entity, ['works', 'categories', 'tags', 'episodes'], true)): ?>
    <section class="panel">
        <!-- Título muda conforme estiver editando ou criando -->
        <h2><?= $editRow ? 'Editar registro' : 'Cadastrar registro' ?></h2>

        <form method="post" class="formbox formbox-wide">
            <!-- Token de segurança (CSRF) -->
            <input type="hidden" name="csrf" value="<?= crud_h(csrf()) ?>">
            <!-- Diz ao sistema qual entidade está sendo salva -->
            <input type="hidden" name="entity" value="<?= crud_h($entity) ?>">
            <!-- Diz ao sistema que a ação é "salvar" -->
            <input type="hidden" name="action" value="save">
            <!-- ID do registro (vazio = criar novo; preenchido = editar) -->
            <input type="hidden" name="id" value="<?= crud_h($editRow['id'] ?? '') ?>">

            <?php if ($entity === 'works'): ?>
                <!-- ---- CAMPOS DE OBRA ---- -->
                <label>Título *</label>
                <input class="input" name="title" required value="<?= crud_h($editRow['title'] ?? '') ?>">

                <label>Slug *</label>
                <input class="input" name="slug" required pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= crud_h($editRow['slug'] ?? '') ?>">

                <label>Categoria principal</label>
                <select class="select" name="category_id">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (string) ($editRow['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                            <?= crud_h($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Tags (segure Ctrl para selecionar várias)</label>
                <select class="select" name="tag_ids[]" multiple size="5">
                    <?php foreach ($tags as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= in_array((int) $t['id'], (array) ($editRow['tag_ids'] ?? []), true) ? 'selected' : '' ?>>
                            <?= crud_h($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Criador</label>
                <input class="input" name="creator" value="<?= crud_h($editRow['creator'] ?? '') ?>">

                <label>Ano de lançamento</label>
                <input class="input" type="number" min="1880" max="<?= date('Y') + 5 ?>" name="release_year" value="<?= crud_h($editRow['release_year'] ?? '') ?>">

                <label>Situação</label>
                <select class="select" name="status">
                    <option value="">Não informada</option>
                    <?php foreach (['Em andamento', 'Concluída', 'Cancelada'] as $s): ?>
                        <option <?= ($editRow['status'] ?? '') === $s ? 'selected' : '' ?>><?= crud_h($s) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Classificação indicativa</label>
                <input class="input" name="classification" value="<?= crud_h($editRow['classification'] ?? '') ?>">

                <label>Descrição</label>
                <textarea class="textarea" name="description" rows="3"><?= crud_h($editRow['description'] ?? '') ?></textarea>

                <label>Avisos de conteúdo</label>
                <textarea class="textarea" name="content_warnings" rows="3"><?= crud_h($editRow['content_warnings'] ?? '') ?></textarea>

            <?php elseif ($entity === 'categories'): ?>
                <!-- ---- CAMPOS DE CATEGORIA ---- -->
                <label>Nome *</label>
                <input class="input" name="name" required value="<?= crud_h($editRow['name'] ?? '') ?>">

                <label>Slug *</label>
                <input class="input" name="slug" required pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= crud_h($editRow['slug'] ?? '') ?>">

                <label>Descrição</label>
                <textarea class="textarea" name="description"><?= crud_h($editRow['description'] ?? '') ?></textarea>

            <?php elseif ($entity === 'tags'): ?>
                <!-- ---- CAMPOS DE TAG ---- -->
                <label>Nome *</label>
                <input class="input" name="name" required value="<?= crud_h($editRow['name'] ?? '') ?>">

                <label>Slug *</label>
                <input class="input" name="slug" required pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= crud_h($editRow['slug'] ?? '') ?>">

            <?php else: ?>
                <!-- ---- CAMPOS DE EPISÓDIO ---- -->
                <label>Obra *</label>
                <select class="select" name="work_id" required>
                    <?php foreach ($works as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= (string) ($editRow['work_id'] ?? '') === (string) $w['id'] ? 'selected' : '' ?>>
                            <?= crud_h($w['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Título do episódio *</label>
                <input class="input" name="title" required value="<?= crud_h($editRow['title'] ?? '') ?>">

                <label>Número do episódio</label>
                <input class="input" type="number" min="1" name="episode_number" value="<?= crud_h($editRow['episode_number'] ?? '') ?>">

                <label>Data de lançamento</label>
                <input class="input" type="date" name="release_date" value="<?= crud_h($editRow['release_date'] ?? '') ?>">

                <label>URL do vídeo / fonte</label>
                <input class="input" type="url" name="video_url" value="<?= crud_h($editRow['video_url'] ?? '') ?>">

                <label>Descrição</label>
                <textarea class="textarea" name="description"><?= crud_h($editRow['description'] ?? '') ?></textarea>
            <?php endif; ?>

            <!-- Botão para salvar -->
            <button class="btn" type="submit">Salvar no PostgreSQL</button>

            <!-- Se estiver editando, mostra botão para cancelar -->
            <?php if ($editRow): ?>
                <a class="btn ghost" href="crud.php?entity=<?= crud_h($entity) ?>">Cancelar edição</a>
            <?php endif; ?>
        </form>
    </section>

<!-- ===== FORMULÁRIOS ESPECIAIS (comentários, usuários, solicitações) ===== --><?php endif; ?>

<?php if (in_array($entity, ['comments', 'users', 'contact_requests'], true)): ?>
<section class="panel">
    <h2><?= $editRow ? 'Editar registro' : 'Cadastrar / atualizar registro' ?></h2>
    <form method="post" class="formbox formbox-wide">
        <input type="hidden" name="csrf" value="<?= crud_h(csrf()) ?>">
        <input type="hidden" name="entity" value="<?= crud_h($entity) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editRow['id'] ?? 0) ?>">
        <?php if ($entity === 'comments'): ?>
            <?php if (!$editRow): ?>
                <label>Usuário *</label><select class="select" name="user_id" required><option value="">Selecione</option><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= crud_h($u['nickname']) ?></option><?php endforeach; ?></select>
                <label>Obra *</label><select class="select" name="work_id" required><option value="">Selecione</option><?php foreach ($works as $w): ?><option value="<?= (int)$w['id'] ?>"><?= crud_h($w['title']) ?></option><?php endforeach; ?></select>
            <?php endif; ?>
            <label>Conteúdo *</label><textarea class="textarea" name="content" required><?= crud_h($editRow['content'] ?? '') ?></textarea>
            <label>Status</label>
            <select class="select" name="status" required>
                <?php foreach (['published'=>'Publicado','pending'=>'Pendente','flagged'=>'Sinalizado','removed'=>'Removido','publicado'=>'Publicado','oculto'=>'Oculto','removido'=>'Removido'] as $value=>$label): ?>
                    <option value="<?= crud_h($value) ?>" <?= ($editRow['status'] ?? 'published') === $value ? 'selected' : '' ?>><?= crud_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        <?php elseif ($entity === 'users'): ?>
            <label>Nome *</label><input class="input" name="name" value="<?= crud_h($editRow['name'] ?? '') ?>" <?= $editRow ? 'readonly' : 'required' ?>>
            <label>Apelido *</label><input class="input" name="nickname" value="<?= crud_h($editRow['nickname'] ?? '') ?>" <?= $editRow ? 'readonly' : 'required' ?>>
            <label>E-mail *</label><input class="input" type="email" name="email" value="<?= crud_h($editRow['email'] ?? '') ?>" <?= $editRow ? 'readonly' : 'required' ?>>
            <?php if (!$editRow): ?><label>Senha (mínimo 8 caracteres) *</label><input class="input" type="password" name="password" minlength="8" required autocomplete="new-password"><?php endif; ?>
            <label>Papel</label><select class="select" name="role_id" required><?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (string)($editRow['role_id'] ?? '') === (string)$r['id'] ? 'selected' : '' ?>><?= crud_h($r['name']) ?></option><?php endforeach; ?></select>
            <label>Status</label><select class="select" name="status" required><?php foreach (['ativo','suspenso','inativo','active','suspended','inactive'] as $s): ?><option value="<?= crud_h($s) ?>" <?= ($editRow['status'] ?? 'ativo') === $s ? 'selected' : '' ?>><?= crud_h($s) ?></option><?php endforeach; ?></select>
        <?php else: ?>
            <label>Nome</label><input class="input" name="name" value="<?= crud_h($editRow['name'] ?? '') ?>">
            <label>E-mail</label><input class="input" type="email" name="email" value="<?= crud_h($editRow['email'] ?? '') ?>">
            <label>Tipo</label><select class="select" name="request_type" required><?php foreach (['correcao','atualizacao','remocao','reclamacao','sugestao','outro'] as $type): ?><option value="<?= crud_h($type) ?>" <?= ($editRow['request_type'] ?? 'outro') === $type ? 'selected' : '' ?>><?= crud_h($type) ?></option><?php endforeach; ?></select>
            <label>Assunto *</label><input class="input" name="subject" value="<?= crud_h($editRow['subject'] ?? '') ?>" <?= $editRow ? 'readonly' : 'required' ?>>
            <?php if (!$editRow): ?><label>Mensagem *</label><textarea class="textarea" name="message" required></textarea><?php endif; ?>
            <label>Status</label><select class="select" name="status" required><?php foreach (['pendente','em_analise','resolvida','rejeitada'] as $s): ?><option value="<?= crud_h($s) ?>" <?= ($editRow['status'] ?? 'pendente') === $s ? 'selected' : '' ?>><?= crud_h($s) ?></option><?php endforeach; ?></select>
        <?php endif; ?>
        <button class="btn" type="submit"><?= $editRow ? 'Salvar alterações' : 'Cadastrar' ?></button>
        <?php if ($editRow): ?><a class="btn ghost" href="crud.php?entity=<?= crud_h($entity) ?>">Cancelar</a><?php endif; ?>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <h2>Registros</h2>
    <form method="get" class="formbox" style="margin-bottom:16px">
        <input type="hidden" name="entity" value="<?= crud_h($entity) ?>">
        <label for="q">Buscar</label>
        <input class="input" id="q" name="q" value="<?= crud_h($_GET['q'] ?? '') ?>" placeholder="Digite para pesquisar">
        <button class="btn" type="submit">Buscar</button>
    </form>
    <?php if (!$rows): ?>
        <p class="muted">Nenhum registro encontrado.</p>
    <?php else: ?>
        <div style="overflow-x:auto"><table class="table">
            <thead><tr><?php foreach (array_keys($rows[0]) as $col): ?><th><?= crud_h($col) ?></th><?php endforeach; ?><th>Ações</th></tr></thead>
            <tbody><?php foreach ($rows as $row): ?><tr>
                <?php foreach ($row as $value): ?><td><?= crud_h(is_scalar($value) || $value === null ? ($value ?? '') : json_encode($value, JSON_UNESCAPED_UNICODE)) ?></td><?php endforeach; ?>
                <td class="hero-actions">
                    <a class="btn ghost" href="crud.php?entity=<?= crud_h($entity) ?>&amp;edit=<?= (int)$row['id'] ?>">Editar</a>
                    <form method="post" onsubmit="return confirm('Confirma a exclusão deste registro?')" style="display:inline">
                        <input type="hidden" name="csrf" value="<?= crud_h(csrf()) ?>"><input type="hidden" name="entity" value="<?= crud_h($entity) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="action" value="delete">
                        <button class="btn ghost" type="submit">Excluir</button>
                    </form>
                </td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
