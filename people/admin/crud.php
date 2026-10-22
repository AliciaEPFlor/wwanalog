<?php
/* WWAnalog — CRUD administrativo PostgreSQL: cria, consulta, atualiza e exclui registros permitidos. */
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
check_csrf();
$page_title = 'CRUD PostgreSQL';
$active = 'admin';
// Lista de entidades permitidas: impede que o nome da tabela venha livremente da URL.
// Esta lista funciona como uma allowlist: somente as entidades previstas podem ser manipuladas.
$allowed = ['works', 'categories', 'tags', 'episodes', 'comments', 'users', 'contact_requests'];
$entity = (string) ($_GET['entity'] ?? $_POST['entity'] ?? 'works');
if (!in_array($entity, $allowed, true))
    $entity = 'works';
$pdo = null;
$error = '';
$notice = '';
$editRow = null;
$rows = [];
$categories = [];
$works = [];
$roles = [];
$entityLabels = ['works' => 'Obras', 'categories' => 'Categorias', 'tags' => 'Tags', 'episodes' => 'Episódios', 'comments' => 'Comentários', 'users' => 'Usuários', 'contact_requests' => 'Solicitações de contato'];
function crud_h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
function crud_nullable(string $key): ?string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    return $v === '' ? null : $v;
}
// Abre a conexão com PostgreSQL e carrega opções necessárias aos formulários.
try {
    $pdo = db();
    $categories = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
    $tags = $pdo->query('SELECT id,name FROM tags ORDER BY name')->fetchAll();
    $works = $pdo->query('SELECT id,title FROM works ORDER BY title')->fetchAll();
    $roles = $pdo->query('SELECT id,name FROM roles ORDER BY name')->fetchAll();
    $users = $pdo->query('SELECT id,nickname FROM users ORDER BY nickname')->fetchAll();
    // Processa gravações apenas após a verificação de CSRF e autorização administrativa.
    // Toda escrita passa por este bloco, depois de exigir administrador e validar CSRF.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        // Exclusão: remove primeiro os vínculos dependentes quando necessário.
        if ($action === 'delete') {
            if (!$id)
                throw new RuntimeException('Registro inválido para exclusão.');
            switch ($entity) {
                // Obra: grava os metadados e sincroniza as relações de categoria e tags.
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
                // Categoria: valida nome e slug antes de inserir ou atualizar.
                case 'categories':
                    $pdo->prepare('DELETE FROM work_categories WHERE category_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
                    break;
                // Tag: mantém uma etiqueta reutilizável para associar às obras.
                case 'tags':
                    $pdo->prepare('DELETE FROM work_tags WHERE tag_id=?')->execute([$id]);
                    $pdo->prepare('DELETE FROM tags WHERE id=?')->execute([$id]);
                    break;
                // Episódio: exige uma obra relacionada e guarda os dados opcionais de publicação.
                case 'episodes':
                    $pdo->prepare('DELETE FROM episodes WHERE id=?')->execute([$id]);
                    break;
                // Comentário: permite cadastrar e moderar conteúdo associado a usuário e obra.
                case 'comments':
                    $pdo->prepare('DELETE FROM comments WHERE id=?')->execute([$id]);
                    break;
                // Usuário: cria contas com hash de senha ou altera status/papel sem expor a senha existente.
                case 'users':
                    throw new RuntimeException('A exclusão de usuários foi bloqueada para preservar avaliações, comentários e listas. Altere o status para suspenso/inativo.');
                // Solicitação: guarda contato e mensagem, permitindo atualizar o status do atendimento.
                case 'contact_requests':
                    $pdo->prepare('DELETE FROM contact_requests WHERE id=?')->execute([$id]);
                    break;
            }
            audit_event('postgres_crud_delete', $entity . ' id=' . $id);
            $notice = 'Registro excluído do PostgreSQL.';
            // Salvamento: valida campos e executa INSERT ou UPDATE com parâmetros PDO.
        } elseif ($action === 'save') {
            switch ($entity) {
                case 'works':
                    $title = trim((string) ($_POST['title'] ?? ''));
                    $slug = trim((string) ($_POST['slug'] ?? ''));
                    if ($title === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug))
                        throw new RuntimeException('Título obrigatório e slug em minúsculas, números e hífens.');
                    $data = [$title, $slug, crud_nullable('description'), crud_nullable('creator'), ($_POST['release_year'] ?? '') !== '' ? (int) $_POST['release_year'] : null, crud_nullable('status'), crud_nullable('classification'), crud_nullable('content_warnings')];
                    if ($id) {
                        $data[] = $id;
                        $pdo->prepare('UPDATE works SET title=?,slug=?,description=?,creator=?,release_year=?,status=?,classification=?,content_warnings=? WHERE id=?')->execute($data);
                        $workId = $id;
                    } else {
                        $st = $pdo->prepare('INSERT INTO works (title,slug,description,creator,release_year,status,classification,content_warnings) VALUES (?,?,?,?,?,?,?,?) RETURNING id');
                        $st->execute($data);
                        $workId = (int) $st->fetchColumn();
                    }
                    $catId = (int) ($_POST['category_id'] ?? 0);
                    $pdo->prepare('DELETE FROM work_categories WHERE work_id=?')->execute([$workId]);
                    if ($catId) {
                        $pdo->prepare('INSERT INTO work_categories (work_id,category_id) VALUES (?,?) ON CONFLICT DO NOTHING')->execute([$workId, $catId]);
                    }
                    $tagIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['tag_ids'] ?? [])), fn($v) => $v > 0)));
                    $pdo->prepare('DELETE FROM work_tags WHERE work_id=?')->execute([$workId]);
                    if ($tagIds) {
                        $tagInsert = $pdo->prepare('INSERT INTO work_tags(work_id,tag_id) VALUES(?,?) ON CONFLICT DO NOTHING');
                        foreach ($tagIds as $tagId)
                            $tagInsert->execute([$workId, $tagId]);
                    }
                    break;
                case 'categories':
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $slug = trim((string) ($_POST['slug'] ?? ''));
                    if ($name === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug))
                        throw new RuntimeException('Nome obrigatório e slug válido.');
                    $vals = [$name, $slug, crud_nullable('description')];
                    if ($id) {
                        $vals[] = $id;
                        $pdo->prepare('UPDATE categories SET name=?,slug=?,description=? WHERE id=?')->execute($vals);
                    } else {
                        $pdo->prepare('INSERT INTO categories(name,slug,description) VALUES(?,?,?)')->execute($vals);
                    }
                    break;
                case 'tags':
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $slug = trim((string) ($_POST['slug'] ?? ''));
                    if ($name === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug))
                        throw new RuntimeException('Nome obrigatório e slug válido.');
                    if ($id)
                        $pdo->prepare('UPDATE tags SET name=?,slug=? WHERE id=?')->execute([$name, $slug, $id]);
                    else
                        $pdo->prepare('INSERT INTO tags(name,slug) VALUES(?,?)')->execute([$name, $slug]);
                    break;
                case 'episodes':
                    $workId = (int) ($_POST['work_id'] ?? 0);
                    $title = trim((string) ($_POST['title'] ?? ''));
                    if (!$workId || $title === '')
                        throw new RuntimeException('Selecione a obra e informe o título do episódio.');
                    $num = ($_POST['episode_number'] ?? '') !== '' ? (int) $_POST['episode_number'] : null;
                    $date = crud_nullable('release_date');
                    $url = crud_nullable('video_url');
                    $desc = crud_nullable('description');
                    if ($id)
                        $pdo->prepare('UPDATE episodes SET work_id=?,title=?,episode_number=?,release_date=?,video_url=?,description=? WHERE id=?')->execute([$workId, $title, $num, $date, $url, $desc, $id]);
                    else
                        $pdo->prepare('INSERT INTO episodes(work_id,title,episode_number,release_date,video_url,description) VALUES(?,?,?,?,?,?)')->execute([$workId, $title, $num, $date, $url, $desc]);
                    break;
                case 'comments':
                    $status = trim((string) ($_POST['status'] ?? 'published'));
                    if (!in_array($status, ['published', 'pending', 'flagged', 'removed', 'publicado', 'oculto', 'removido'], true))
                        throw new RuntimeException('Situação de comentário inválida.');
                    $content = trim((string) ($_POST['content'] ?? ''));
                    $userId = (int) ($_POST['user_id'] ?? 0);
                    $workId = (int) ($_POST['work_id'] ?? 0);
                    if ($content === '')
                        throw new RuntimeException('O comentário não pode ficar vazio.');
                    if ($id)
                        $pdo->prepare('UPDATE comments SET content=?,status=? WHERE id=?')->execute([$content, $status, $id]);
                    else {
                        if (!$userId || !$workId)
                            throw new RuntimeException('Selecione o usuário e a obra.');
                        $pdo->prepare('INSERT INTO comments(user_id,work_id,content,status) VALUES(?,?,?,?)')->execute([$userId, $workId, $content, $status]);
                    }
                    break;
                case 'users':
                    $status = trim((string) ($_POST['status'] ?? 'ativo'));
                    $roleId = (int) ($_POST['role_id'] ?? 0);
                    if (!in_array($status, ['ativo', 'suspenso', 'inativo', 'active', 'suspended', 'inactive'], true) || !$roleId)
                        throw new RuntimeException('Selecione um status e papel válidos.');
                    if ($id)
                        $pdo->prepare('UPDATE users SET status=?,role_id=? WHERE id=?')->execute([$status, $roleId, $id]);
                    else {
                        $name = trim((string) ($_POST['name'] ?? ''));
                        $nickname = trim((string) ($_POST['nickname'] ?? ''));
                        $email = trim((string) ($_POST['email'] ?? ''));
                        $password = (string) ($_POST['password'] ?? '');
                        if ($name === '' || $nickname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8)
                            throw new RuntimeException('Informe nome, apelido, e-mail válido e senha com pelo menos 8 caracteres.');
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare('INSERT INTO users(name,nickname,email,password_hash,role_id,status) VALUES(?,?,?,?,?,?)')->execute([$name, $nickname, $email, $hash, $roleId, $status]);
                    }
                    break;
                case 'contact_requests':
                    $status = trim((string) ($_POST['status'] ?? 'pendente'));
                    if (!in_array($status, ['pendente', 'em_analise', 'resolvida', 'rejeitada', 'pending', 'in_review', 'resolved', 'rejected'], true))
                        throw new RuntimeException('Situação de solicitação inválida.');
                    if ($id)
                        $pdo->prepare('UPDATE contact_requests SET status=? WHERE id=?')->execute([$status, $id]);
                    else {
                        $name = crud_nullable('name');
                        $email = crud_nullable('email');
                        $type = trim((string) ($_POST['request_type'] ?? 'outro'));
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
            audit_event('postgres_crud_save', $entity . ' id=' . ($id ?: 'novo'));
            $notice = 'Alterações gravadas no PostgreSQL.';
        }
    }
    // Carrega um registro existente para preencher o formulário de edição.
    // Ao editar, busca o registro e carrega também os vínculos de categoria/tag da obra.
    if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
        $id = (int) $_GET['edit'];
        $table = ['works' => 'works', 'categories' => 'categories', 'tags' => 'tags', 'episodes' => 'episodes', 'comments' => 'comments', 'users' => 'users', 'contact_requests' => 'contact_requests'][$entity];
        $st = $pdo->prepare("SELECT * FROM {$table} WHERE id=?");
        $st->execute([$id]);
        $editRow = $st->fetch() ?: null;
        if (!$editRow)
            $error = 'Registro não encontrado.';
        if ($entity === 'works' && $editRow) {
            $st = $pdo->prepare('SELECT category_id FROM work_categories WHERE work_id=? LIMIT 1');
            $st->execute([$id]);
            $editRow['category_id'] = $st->fetchColumn() ?: '';
            $st = $pdo->prepare('SELECT tag_id FROM work_tags WHERE work_id=?');
            $st->execute([$id]);
            $editRow['tag_ids'] = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        }
    }
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
    [$sql, $paramFn] = $queries[$entity];
    $st = $pdo->prepare($sql);
    $st->execute($paramFn($q));
    $rows = $st->fetchAll();
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction())
        $pdo->rollBack();
    $error = $e instanceof PDOException ? 'Erro no PostgreSQL. Confira se o esquema das tabelas corresponde ao guia do projeto e se a conexão está configurada.' : $e->getMessage();
}
require __DIR__ . '/../../includes/header.php';
?>
<div class="section-head">
    <div>
        <div class="eyebrow">PAINEL ADMINISTRATIVO · POSTGRESQL</div>
        <h1>CRUD — <?= crud_h($entityLabels[$entity]) ?></h1>
        <p class="small muted">Cadastrar, consultar, editar e excluir quando aplicável. As alterações desta tela são
            executadas no banco PostgreSQL.</p>
    </div><a class="btn ghost" href="index.php">Painel</a>
</div>
<?php if ($error): ?>
    <div class="notice" role="alert"><?= crud_h($error) ?></div><?php endif; ?><?php if ($notice): ?>
    <div class="notice success" role="status"><?= crud_h($notice) ?></div><?php endif; ?>
<div class="hero-actions" style="margin-bottom:18px;flex-wrap:wrap"><?php foreach ($entityLabels as $key => $label): ?><a
            class="btn <?= $entity === $key ? '' : 'ghost' ?>"
            href="crud.php?entity=<?= crud_h($key) ?>"><?= crud_h($label) ?></a><?php endforeach; ?></div>
<?php if (in_array($entity, ['works', 'categories', 'tags', 'episodes'], true)): ?>
    <section class="panel">
        <h2><?= $editRow ? 'Editar registro' : 'Cadastrar registro' ?></h2>
        <form method="post" class="formbox formbox-wide"><input type="hidden" name="csrf" value="<?= crud_h(csrf()) ?>"><input
                type="hidden" name="entity" value="<?= crud_h($entity) ?>"><input type="hidden" name="action"
                value="save"><input type="hidden" name="id" value="<?= crud_h($editRow['id'] ?? '') ?>">
            <?php if ($entity === 'works'): ?>
                <label>Título *</label><input class="input" name="title" required
                    value="<?= crud_h($editRow['title'] ?? '') ?>"><label>Slug *</label><input class="input" name="slug" required
                    pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= crud_h($editRow['slug'] ?? '') ?>"><label>Categoria
                    principal</label><select class="select" name="category_id">
                    <option value="">Sem categoria</option><?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (string) ($editRow['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                            <?= crud_h($c['name']) ?></option><?php endforeach; ?>
                </select><label>Tags (segure Ctrl para selecionar várias)</label><select class="select" name="tag_ids[]"
                    multiple size="5"><?php foreach ($tags as $t): ?>
                        <option value="<?= $t['id'] ?>"
                            <?= in_array((int) $t['id'], (array) ($editRow['tag_ids'] ?? []), true) ? 'selected' : '' ?>><?= crud_h($t['name']) ?>
                        </option><?php endforeach; ?>
                </select><label>Criador</label><input class="input" name="creator"
                    value="<?= crud_h($editRow['creator'] ?? '') ?>"><label>Ano de lançamento</label><input class="input"
                    type="number" min="1880" max="<?= date('Y') + 5 ?>" name="release_year"
                    value="<?= crud_h($editRow['release_year'] ?? '') ?>"><label>Situação</label><select class="select"
                    name="status">
                    <option value="">Não informada</option><?php foreach (['Em andamento', 'Concluída', 'Cancelada'] as $s): ?>
                        <option <?= ($editRow['status'] ?? '') === $s ? 'selected' : '' ?>><?= crud_h($s) ?></option><?php endforeach; ?>
                </select><label>Classificação indicativa</label><input class="input" name="classification"
                    value="<?= crud_h($editRow['classification'] ?? '') ?>"><label>Descrição</label><textarea class="textarea"
                    name="description" rows="3"><?= crud_h($editRow['description'] ?? '') ?></textarea><label>Avisos de
                    conteúdo</label><textarea class="textarea" name="content_warnings"
                    rows="3"><?= crud_h($editRow['content_warnings'] ?? '') ?></textarea>
            <?php elseif ($entity === 'categories'): ?><label>Nome *</label><input class="input" name="name" required
                    value="<?= crud_h($editRow['name'] ?? '') ?>"><label>Slug *</label><input class="input" name="slug" required
                    pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= crud_h($editRow['slug'] ?? '') ?>"><label>Descrição</label><textarea
                    class="textarea" name="description"><?= crud_h($editRow['description'] ?? '') ?></textarea>
            <?php elseif ($entity === 'tags'): ?><label>Nome *</label><input class="input" name="name" required
                    value="<?= crud_h($editRow['name'] ?? '') ?>"><label>Slug *</label><input class="input" name="slug" required
                    pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= crud_h($editRow['slug'] ?? '') ?>">
            <?php else: ?><label>Obra *</label><select class="select" name="work_id" required><?php foreach ($works as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= (string) ($editRow['work_id'] ?? '') === (string) $w['id'] ? 'selected' : '' ?>>
                            <?= crud_h($w['title']) ?></option><?php endforeach; ?>
                </select><label>Título do episódio *</label><input class="input" name="title" required
                    value="<?= crud_h($editRow['title'] ?? '') ?>"><label>Número do episódio</label><input class="input"
                    type="number" min="1" name="episode_number" value="<?= crud_h($editRow['episode_number'] ?? '') ?>"><label>Data
                    de lançamento</label><input class="input" type="date" name="release_date"
                    value="<?= crud_h($editRow['release_date'] ?? '') ?>"><label>URL do vídeo / fonte</label><input class="input"
                    type="url" name="video_url" value="<?= crud_h($editRow['video_url'] ?? '') ?>"><label>Descrição</label><textarea
                    class="textarea" name="description"><?= crud_h($editRow['description'] ?? '') ?></textarea><?php endif; ?>
            <button class="btn" type="submit">Salvar no PostgreSQL</button><?php if ($editRow): ?><a class="btn ghost"
                    href="crud.php?entity=<?= crud_h($entity) ?>">Cancelar edição</a><?php endif; ?>
        </form>
    </section>
<?php elseif (in_array($entity, ['comments', 'users', 'contact_requests'], true)): ?>
    <section class="panel">
        <h2><?php if ($entity === 'comments'): ?>Cadastrar comentário<?php elseif ($entity === 'users'): ?>Cadastrar
                usuário<?php else: ?>Cadastrar solicitação de contato<?php endif; ?></h2>
        <form method="post" class="formbox formbox-wide"><input type="hidden" name="csrf" value="<?= crud_h(csrf()) ?>"><input
                type="hidden" name="entity" value="<?= crud_h($entity) ?>"><input type="hidden" name="action"
                value="save"><input type="hidden" name="id"
                value="0"><?php if ($entity === 'comments'): ?><label>Usuário</label><select class="select" name="user_id"
                    required><?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= crud_h($u['nickname']) ?></option><?php endforeach; ?>
                </select><label>Obra</label><select class="select" name="work_id" required><?php foreach ($works as $w): ?>
                        <option value="<?= $w['id'] ?>"><?= crud_h($w['title']) ?></option><?php endforeach; ?>
                </select><label>Comentário</label><textarea class="textarea" name="content" required
                    rows="4"></textarea><label>Status</label><select class="select" name="status">
                    <option value="published">published</option>
                    <option value="pending">pending</option>
                    <option value="flagged">flagged</option>
                    <option value="removed">removed</option>
                </select><?php elseif ($entity === 'users'): ?><label>Nome completo</label><input class="input" name="name"
                    required><label>Apelido</label><input class="input" name="nickname" required><label>E-mail</label><input
                    class="input" type="email" name="email" required><label>Senha inicial (mínimo 8 caracteres)</label><input
                    class="input" type="password" name="password" minlength="8" required><label>Papel</label><select
                    class="select" name="role_id" required><?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= crud_h($r['name']) ?></option><?php endforeach; ?>
                </select><label>Status</label><select class="select" name="status">
                    <option>ativo</option>
                    <option>suspenso</option>
                    <option>inativo</option>
                </select><?php else: ?><label>Nome</label><input class="input" name="name"><label>E-mail</label><input
                    class="input" type="email" name="email"><label>Tipo</label><select class="select"
                    name="request_type"><?php foreach (['correcao', 'atualizacao', 'remocao', 'reclamacao', 'sugestao', 'outro'] as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                </select><label>Assunto</label><input class="input" name="subject" required><label>Mensagem</label><textarea
                    class="textarea" name="message" required rows="4"></textarea><label>Status</label><select class="select"
                    name="status">
                    <option>pendente</option>
                    <option>em_analise</option>
                    <option>resolvida</option>
                    <option>rejeitada</option>
                </select><?php endif; ?><button class="btn" type="submit">Cadastrar no PostgreSQL</button></form>
        <p class="small muted">Usuários não podem ser excluídos por esta tela, para preservar seus vínculos e histórico.</p>
    </section><?php endif; ?>
<section class="panel" style="margin-top:18px">
    <h2>Registros cadastrados</h2>
    <form method="get" class="filter-bar" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0"><input
            type="hidden" name="entity" value="<?= crud_h($entity) ?>"><input class="input" name="q"
            value="<?= crud_h($_GET['q'] ?? '') ?>" placeholder="Buscar nos registros"><button class="btn">Pesquisar com
            SQL</button><a class="btn ghost" href="crud.php?entity=<?= crud_h($entity) ?>">Limpar</a></form>
    <div class="table">
        <table class="table">
            <thead>
                <tr><?php if ($rows):
                    foreach (array_keys($rows[0]) as $key): ?>
                            <th><?= crud_h($key) ?></th><?php endforeach; ?>
                        <th>Ações</th><?php else: ?>
                        <th>Resultado</th><?php endif; ?>
                </tr>
            </thead>
            <tbody><?php if (!$rows): ?>
                    <tr>
                        <td colspan="10">Nenhum registro encontrado.</td>
                    </tr><?php else:
                foreach ($rows as $row): ?>
                        <tr><?php foreach ($row as $value): ?>
                                <td><?= crud_h(is_scalar($value) ? $value : '') ?></td><?php endforeach; ?>
                            <td>
                                <div class="hero-actions" style="gap:6px;flex-wrap:wrap">
                                    <?php if (in_array($entity, ['works', 'categories', 'tags', 'episodes'], true)): ?><a
                                            class="btn ghost"
                                            href="crud.php?entity=<?= crud_h($entity) ?>&edit=<?= (int) $row['id'] ?>">Editar</a><?php elseif ($entity === 'comments' || $entity === 'users' || $entity === 'contact_requests'): ?><button
                                            class="btn ghost" type="button"
                                            onclick="document.getElementById('edit-<?= (int) $row['id'] ?>').hidden=false">Gerenciar</button><?php endif; ?><?php if (!in_array($entity, ['users'], true)): ?>
                                        <form method="post"
                                            onsubmit="return confirm('Confirma a exclusão deste registro do PostgreSQL?')"><input
                                                type="hidden" name="csrf" value="<?= crud_h(csrf()) ?>"><input type="hidden"
                                                name="entity" value="<?= crud_h($entity) ?>"><input type="hidden" name="action"
                                                value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button
                                                class="btn danger" type="submit">Excluir</button></form><?php endif; ?>
                                </div>
                                <?php if (in_array($entity, ['comments', 'users', 'contact_requests'], true)): ?>
                                    <form id="edit-<?= (int) $row['id'] ?>" hidden method="post" style="margin-top:8px"><input
                                            type="hidden" name="csrf" value="<?= crud_h(csrf()) ?>"><input type="hidden" name="entity"
                                            value="<?= crud_h($entity) ?>"><input type="hidden" name="action" value="save"><input
                                            type="hidden" name="id"
                                            value="<?= (int) $row['id'] ?>"><?php if ($entity === 'comments'): ?><textarea class="textarea"
                                                name="content" required rows="3"><?= crud_h($row['content'] ?? '') ?></textarea><select
                                                class="select"
                                                name="status"><?php foreach (['published', 'pending', 'flagged', 'removed'] as $s): ?>
                                                    <option value="<?= $s ?>" <?= ($row['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select><?php elseif ($entity === 'users'): ?><select class="select"
                                                name="status"><?php foreach (['ativo', 'suspenso', 'inativo'] as $s): ?>
                                                    <option value="<?= $s ?>" <?= ($row['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select><select class="select" name="role_id"><?php foreach ($roles as $r): ?>
                                                    <option value="<?= $r['id'] ?>"
                                                        <?= (string) ($row['role_id'] ?? '') === (string) $r['id'] ? 'selected' : '' ?>>
                                                        <?= crud_h($r['name']) ?></option><?php endforeach; ?>
                                            </select><?php else: ?><select class="select"
                                                name="status"><?php foreach (['pendente', 'em_analise', 'resolvida', 'rejeitada'] as $s): ?>
                                                    <option value="<?= $s ?>" <?= ($row['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select><?php endif; ?><button class="btn" type="submit">Salvar</button></form>
                                <?php endif; ?>
                            </td>
                        </tr><?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <p class="small muted">Limite de 200 registros nesta listagem. Use a busca para localizar registros.</p>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>