<?php
/* WWAnalog — Consulta administrativa PostgreSQL: aplica filtros, ordenação segura e paginação. */
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Administração PostgreSQL';
$active = 'admin';
require __DIR__ . '/../../includes/header.php';

function pg_h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
// Entidades explicitamente permitidas para consulta.
$allowed = ['works', 'users', 'episodes', 'categories', 'tags', 'comments', 'ratings', 'user_lists', 'contact_requests', 'audit_logs'];
$entity = (string) ($_GET['entity'] ?? 'works');
if (!in_array($entity, $allowed, true))
    $entity = 'works';
$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$yearFrom = trim((string) ($_GET['year_from'] ?? ''));
$yearTo = trim((string) ($_GET['year_to'] ?? ''));
$score = trim((string) ($_GET['score'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$requestType = trim((string) ($_GET['request_type'] ?? ''));
$role = trim((string) ($_GET['role'] ?? ''));
$rows = [];
$error = '';
$total = 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
// Lista de colunas de ordenação permitidas para cada entidade.
// A ordenação usa uma lista permitida para impedir que parâmetros da URL virem SQL arbitrário.
$sortMap = [
    'works' => ['title', 'release_year', 'created_at'],
    'users' => ['name', 'nickname', 'email', 'created_at'],
    'episodes' => ['title', 'episode_number', 'release_date'],
    'categories' => ['name', 'slug', 'created_at'],
    'tags' => ['name', 'slug'],
    'comments' => ['created_at', 'status'],
    'ratings' => ['score', 'created_at'],
    'user_lists' => ['list_status', 'created_at'],
    'contact_requests' => ['created_at', 'status'],
    'audit_logs' => ['created_at', 'action']
];
$sort = (string) ($_GET['sort'] ?? ($sortMap[$entity][0] ?? 'created_at'));
if (!in_array($sort, $sortMap[$entity], true))
    $sort = $sortMap[$entity][0];
// Mapeia as opções de ordenação para nomes SQL qualificados e não fornecidos pelo usuário.
$sortSqlMap = [
    'works' => ['title' => 'w.title', 'release_year' => 'w.release_year', 'created_at' => 'w.created_at'],
    'users' => ['name' => 'u.name', 'nickname' => 'u.nickname', 'email' => 'u.email', 'created_at' => 'u.created_at'],
    'episodes' => ['title' => 'e.title', 'episode_number' => 'e.episode_number', 'release_date' => 'e.release_date'],
    'categories' => ['name' => 'name', 'slug' => 'slug', 'created_at' => 'created_at'],
    'tags' => ['name' => 'name', 'slug' => 'slug'],
    'comments' => ['created_at' => 'c.created_at', 'status' => 'c.status'],
    'ratings' => ['score' => 'r.score', 'created_at' => 'r.created_at'],
    'user_lists' => ['list_status' => 'ul.list_status', 'created_at' => 'ul.created_at'],
    'contact_requests' => ['created_at' => 'created_at', 'status' => 'status'],
    'audit_logs' => ['created_at' => 'created_at', 'action' => 'action']
];
$direction = strtolower((string) ($_GET['direction'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

// Monta a consulta correspondente à entidade selecionada e executa com parâmetros preparados.
try {
    $pdo = db();
    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    $roles = $pdo->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();
    // Condições e valores ficam separados para evitar concatenar dados do usuário no SQL.
    $where = [];
    $params = [];
    $like = function (string $expr, string $value) use (&$where, &$params) {
        $where[] = "$expr ILIKE ?";
        $params[] = '%' . $value . '%';
    };
    // Cada entidade monta sua própria consulta e adiciona condições somente quando há filtros informados.
    switch ($entity) {
        // Obras: pesquisa texto, situação, categoria e faixa de anos.
        case 'works':
            if ($q !== '')
                $like("(w.title || ' ' || COALESCE(w.creator,'') || ' ' || COALESCE(w.description,''))", $q);
            if ($status !== '') {
                $where[] = 'w.status = ?';
                $params[] = $status;
            }
            if ($category !== '') {
                $where[] = 'EXISTS (SELECT 1 FROM work_categories wc WHERE wc.work_id=w.id AND wc.category_id=?)';
                $params[] = (int) $category;
            }
            if ($yearFrom !== '' && ctype_digit($yearFrom)) {
                $where[] = 'w.release_year >= ?';
                $params[] = (int) $yearFrom;
            }
            if ($yearTo !== '' && ctype_digit($yearTo)) {
                $where[] = 'w.release_year <= ?';
                $params[] = (int) $yearTo;
            }
            $select = "SELECT w.id,w.title,w.slug,w.creator,w.release_year,w.status,w.classification,w.created_at,
                (SELECT string_agg(c.name, ', ' ORDER BY c.name) FROM work_categories wc JOIN categories c ON c.id=wc.category_id WHERE wc.work_id=w.id) AS categories
                FROM works w";
            $columns = ['ID' => 'id', 'Título' => 'title', 'Criador' => 'creator', 'Ano' => 'release_year', 'Situação' => 'status', 'Classificação' => 'classification', 'Categoria(s)' => 'categories', 'Criada em' => 'created_at'];
            break;
        // Usuários: permite pesquisar por dados de perfil, papel, status e data de cadastro.
        case 'users':
            if ($q !== '')
                $like("(u.name || ' ' || u.nickname || ' ' || u.email)", $q);
            if ($status !== '') {
                $where[] = 'u.status=?';
                $params[] = $status;
            }
            if ($role !== '') {
                $where[] = 'r.id=?';
                $params[] = (int) $role;
            }
            if ($dateFrom !== '') {
                $where[] = 'u.created_at >= ?::date';
                $params[] = $dateFrom;
            }
            if ($dateTo !== '') {
                $where[] = "u.created_at < (?::date + INTERVAL '1 day')";
                $params[] = $dateTo;
            }
            $select = "SELECT u.id,u.name,u.nickname,u.email,u.status,r.name AS role,u.created_at FROM users u LEFT JOIN roles r ON r.id=u.role_id";
            $columns = ['ID' => 'id', 'Nome' => 'name', 'Apelido' => 'nickname', 'E-mail' => 'email', 'Papel' => 'role', 'Situação' => 'status', 'Cadastro' => 'created_at'];
            break;
        // Episódios: permite filtrar por obra relacionada e data de lançamento.
        case 'episodes':
            if ($q !== '')
                $like("(e.title || ' ' || COALESCE(e.description,'') || ' ' || w.title)", $q);
            if ($category !== '') {
                $where[] = 'e.work_id=?';
                $params[] = (int) $category;
            }
            if ($dateFrom !== '') {
                $where[] = 'e.release_date >= ?::date';
                $params[] = $dateFrom;
            }
            if ($dateTo !== '') {
                $where[] = 'e.release_date <= ?::date';
                $params[] = $dateTo;
            }
            $select = "SELECT e.id,e.title,e.episode_number,e.release_date,e.video_url,w.title AS work_title FROM episodes e JOIN works w ON w.id=e.work_id";
            $columns = ['ID' => 'id', 'Obra' => 'work_title', 'Episódio' => 'episode_number', 'Título' => 'title', 'Lançamento' => 'release_date', 'Vídeo' => 'video_url'];
            break;
        case 'categories':
            if ($q !== '')
                $like("(name || ' ' || slug || ' ' || COALESCE(description,''))", $q);
            $select = "SELECT id,name,slug,description,created_at FROM categories";
            $columns = ['ID' => 'id', 'Nome' => 'name', 'Slug' => 'slug', 'Descrição' => 'description', 'Criada em' => 'created_at'];
            break;
        case 'tags':
            if ($q !== '')
                $like("(name || ' ' || slug)", $q);
            $select = "SELECT id,name,slug FROM tags";
            $columns = ['ID' => 'id', 'Nome' => 'name', 'Slug' => 'slug'];
            break;
        // Comentários: permite pesquisar conteúdo, autor, situação e data.
        case 'comments':
            if ($q !== '')
                $like("(c.content || ' ' || u.nickname || ' ' || w.title)", $q);
            if ($status !== '') {
                $where[] = 'c.status=?';
                $params[] = $status;
            }
            if ($dateFrom !== '') {
                $where[] = 'c.created_at >= ?::date';
                $params[] = $dateFrom;
            }
            if ($dateTo !== '') {
                $where[] = "c.created_at < (?::date + INTERVAL '1 day')";
                $params[] = $dateTo;
            }
            $select = "SELECT c.id,u.nickname,w.title AS work_title,c.content,c.status,c.created_at FROM comments c JOIN users u ON u.id=c.user_id JOIN works w ON w.id=c.work_id";
            $columns = ['ID' => 'id', 'Usuário' => 'nickname', 'Obra' => 'work_title', 'Comentário' => 'content', 'Situação' => 'status', 'Data' => 'created_at'];
            break;
        // Avaliações: permite filtrar notas de 1 a 5 e período de criação.
        case 'ratings':
            if ($q !== '')
                $like("(u.nickname || ' ' || w.title)", $q);
            if ($score !== '' && ctype_digit($score) && (int) $score >= 1 && (int) $score <= 5) {
                $where[] = 'r.score=?';
                $params[] = (int) $score;
            }
            if ($dateFrom !== '') {
                $where[] = 'r.created_at >= ?::date';
                $params[] = $dateFrom;
            }
            if ($dateTo !== '') {
                $where[] = "r.created_at < (?::date + INTERVAL '1 day')";
                $params[] = $dateTo;
            }
            $select = "SELECT r.id,u.nickname,w.title AS work_title,r.score,r.created_at FROM ratings r JOIN users u ON u.id=r.user_id JOIN works w ON w.id=r.work_id";
            $columns = ['ID' => 'id', 'Usuário' => 'nickname', 'Obra' => 'work_title', 'Nota' => 'score', 'Data' => 'created_at'];
            break;
        // Listas pessoais: consulta estado da lista e obra/usuário associado.
        case 'user_lists':
            if ($q !== '')
                $like("(u.nickname || ' ' || w.title)", $q);
            if ($status !== '') {
                $where[] = 'ul.list_status=?';
                $params[] = $status;
            }
            $select = "SELECT ul.id,u.nickname,w.title AS work_title,ul.list_status,ul.created_at FROM user_lists ul JOIN users u ON u.id=ul.user_id JOIN works w ON w.id=ul.work_id";
            $columns = ['ID' => 'id', 'Usuário' => 'nickname', 'Obra' => 'work_title', 'Estado' => 'list_status', 'Adicionada em' => 'created_at'];
            break;
        // Solicitações: pesquisa tipo, status, texto e datas de entrada.
        case 'contact_requests':
            if ($q !== '')
                $like("(COALESCE(name,'') || ' ' || COALESCE(email,'') || ' ' || subject || ' ' || message)", $q);
            if ($status !== '') {
                $where[] = 'status=?';
                $params[] = $status;
            }
            if ($requestType !== '') {
                $where[] = 'request_type=?';
                $params[] = $requestType;
            }
            if ($dateFrom !== '') {
                $where[] = 'created_at >= ?::date';
                $params[] = $dateFrom;
            }
            if ($dateTo !== '') {
                $where[] = "created_at < (?::date + INTERVAL '1 day')";
                $params[] = $dateTo;
            }
            $select = "SELECT id,name,email,request_type,subject,status,created_at FROM contact_requests";
            $columns = ['ID' => 'id', 'Nome' => 'name', 'E-mail' => 'email', 'Tipo' => 'request_type', 'Assunto' => 'subject', 'Situação' => 'status', 'Data' => 'created_at'];
            break;
        // Auditoria: consulta eventos registrados para acompanhamento administrativo.
        case 'audit_logs':
            if ($q !== '')
                $like("(action || ' ' || COALESCE(entity_type,'') || ' ' || COALESCE(details::text,''))", $q);
            if ($dateFrom !== '') {
                $where[] = 'created_at >= ?::date';
                $params[] = $dateFrom;
            }
            if ($dateTo !== '') {
                $where[] = "created_at < (?::date + INTERVAL '1 day')";
                $params[] = $dateTo;
            }
            $select = "SELECT id,user_id,action,entity_type,entity_id,details,ip_address,created_at FROM audit_logs";
            $columns = ['ID' => 'id', 'Usuário ID' => 'user_id', 'Ação' => 'action', 'Entidade' => 'entity_type', 'ID entidade' => 'entity_id', 'Detalhes' => 'details', 'IP' => 'ip_address', 'Data' => 'created_at'];
            break;
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM (" . $select . $whereSql . ") filtered_rows");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare($select . $whereSql . " ORDER BY " . $sortSqlMap[$entity][$sort] . " " . $direction . " LIMIT " . $perPage . " OFFSET " . $offset);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Não foi possível consultar o PostgreSQL. Confira database/config.local.php, a extensão pdo_pgsql e se as 13 tabelas já foram criadas. Consulte o log do servidor para detalhes técnicos.';
}
function pg_query_url(array $replace = []): string
{
    $params = array_merge($_GET, $replace);
    foreach ($params as $k => $v)
        if ($v === '' || $v === null)
            unset($params[$k]);
    return 'postgres.php?' . http_build_query($params);
}
?>
<div class="breadcrumbs">Área restrita / Administração / PostgreSQL</div>
<div class="section-head">
    <div>
        <div class="eyebrow">DADOS RELACIONAIS</div>
        <h1>Filtros do PostgreSQL</h1>
        <p class="small muted">Consultas SQL de leitura com busca, filtros, ordenação e paginação. As telas antigas
            ainda usam database/data.php.</p>
    </div><a class="btn" href="crud.php?entity=works">Abrir CRUD PostgreSQL</a>
</div>
<div class="panel">
    <form method="get" class="filter-bar" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0;align-items:end">
        <label> Tabela / módulo
            <select class="select" name="entity">
                <?php foreach (['works' => 'Obras', 'users' => 'Usuários', 'episodes' => 'Episódios', 'categories' => 'Categorias', 'tags' => 'Tags', 'comments' => 'Comentários', 'ratings' => 'Avaliações', 'user_lists' => 'Listas pessoais', 'contact_requests' => 'Solicitações', 'audit_logs' => 'Auditoria'] as $key => $label): ?>
                    <option value="<?= pg_h($key) ?>" <?= $entity === $key ? 'selected' : '' ?>><?= pg_h($label) ?></option>
                <?php endforeach; ?>
            </select></label>
        <label>Busca geral<input class="input" name="q" value="<?= pg_h($q) ?>"
                placeholder="Título, nome, conteúdo..."></label>
        <?php if (in_array($entity, ['works', 'users', 'comments', 'user_lists', 'contact_requests'], true)): ?>
            <label>Situação<select class="select" name="status">
                    <option value="">Todas</option>
                    <?php $opts = $entity === 'users' ? ['ativo', 'suspenso', 'inativo'] : ($entity === 'comments' ? ['publicado', 'oculto', 'removido'] : ($entity === 'user_lists' ? ['minha_lista', 'assistindo', 'assistido'] : ($entity === 'contact_requests' ? ['pendente', 'em_analise', 'resolvida', 'rejeitada'] : ['Em andamento', 'Concluída', 'Cancelada'])));
                    foreach ($opts as $op): ?>
                        <option value="<?= pg_h($op) ?>" <?= $status === $op ? 'selected' : '' ?>><?= pg_h($op) ?></option>
                    <?php endforeach; ?>
                </select></label>
        <?php endif; ?>
        <?php if ($entity === 'works'): ?>
            <label>Categoria<select class="select" name="category">
                    <option value="">Todas</option><?php foreach (($categories ?? []) as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (string) $category === (string) $c['id'] ? 'selected' : '' ?>>
                            <?= pg_h($c['name']) ?></option><?php endforeach; ?>
                </select></label>
            <label>Ano de<input class="input" type="number" min="1800" max="2200" name="year_from"
                    value="<?= pg_h($yearFrom) ?>" placeholder="De"></label><label>Ano até<input class="input" type="number"
                    min="1800" max="2200" name="year_to" value="<?= pg_h($yearTo) ?>" placeholder="Até"></label>
        <?php endif; ?>
        <?php if ($entity === 'users'): ?><label>Papel<select class="select" name="role">
                    <option value="">Todos</option><?php foreach (($roles ?? []) as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (string) $role === (string) $r['id'] ? 'selected' : '' ?>><?= pg_h($r['name']) ?>
                        </option><?php endforeach; ?>
                </select></label><?php endif; ?>
        <?php if ($entity === 'episodes'): ?><label>Obra<select class="select" name="category">
                    <option value="">Todas</option>
                    <?php foreach (($pdo->query('SELECT id,title FROM works ORDER BY title')->fetchAll() ?? []) as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= (string) $category === (string) $w['id'] ? 'selected' : '' ?>>
                            <?= pg_h($w['title']) ?></option><?php endforeach; ?>
                </select></label><?php endif; ?>
        <?php if ($entity === 'ratings'): ?><label>Nota<select class="select" name="score">
                    <option value="">Todas</option><?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= $score === (string) $i ? 'selected' : '' ?>><?= $i ?> estrela(s)</option>
                    <?php endfor; ?>
                </select></label><?php endif; ?>
        <?php if ($entity === 'contact_requests'): ?><label>Tipo<select class="select" name="request_type">
                    <option value="">Todos</option>
                    <?php foreach (['correcao', 'atualizacao', 'remocao', 'reclamacao', 'sugestao', 'outro'] as $op): ?>
                        <option value="<?= $op ?>" <?= $requestType === $op ? 'selected' : '' ?>><?= pg_h($op) ?></option>
                    <?php endforeach; ?>
                </select></label><?php endif; ?>
        <?php if (in_array($entity, ['users', 'episodes', 'comments', 'ratings', 'contact_requests', 'audit_logs'], true)): ?><label>Data
                inicial<input class="input" type="date" name="date_from" value="<?= pg_h($dateFrom) ?>"></label><label>Data
                final<input class="input" type="date" name="date_to" value="<?= pg_h($dateTo) ?>"></label><?php endif; ?>
        <label>Ordenar por<select class="select" name="sort"><?php foreach ($sortMap[$entity] as $col): ?>
                    <option value="<?= pg_h($col) ?>" <?= $sort === $col ? 'selected' : '' ?>><?= pg_h($col) ?></option>
                <?php endforeach; ?>
            </select></label>
        <label>Direção<select class="select" name="direction">
                <option value="desc" <?= $direction === 'DESC' ? 'selected' : '' ?>>Decrescente</option>
                <option value="asc" <?= $direction === 'ASC' ? 'selected' : '' ?>>Crescente</option>
            </select></label>
        <button class="btn">Filtrar</button><a class="btn ghost"
            href="postgres.php?entity=<?= pg_h($entity) ?>">Limpar</a>
    </form>
    <?php if ($error): ?>
        <div class="notice"><?= pg_h($error) ?></div><?php else: ?>
        <p class="small muted"><?= number_format($total, 0, ',', '.') ?> registro(s) encontrado(s). Exibindo até <?= $perPage ?>
            por página.</p>
        <div class="table">
            <table class="table">
                <thead>
                    <tr><?php foreach ($columns as $label => $key): ?>
                            <th><?= pg_h($label) ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="<?= count($columns) ?>">Nenhum registro encontrado.</td>
                        </tr><?php else:
                        foreach ($rows as $row): ?>
                            <tr><?php foreach ($columns as $label => $key):
                                $value = $row[$key] ?? '';
                                if (is_array($value) || is_object($value))
                                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
                                    <td><?= pg_h(is_scalar($value) ? $value : '') ?></td><?php endforeach; ?>
                            </tr><?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="hero-actions" style="margin-top:14px"><?php if ($page > 1): ?><a class="btn ghost"
                    href="<?= pg_h(pg_query_url(['page' => $page - 1])) ?>">Página
                    anterior</a><?php endif; ?><?php if ($page * $perPage < $total): ?><a class="btn ghost"
                    href="<?= pg_h(pg_query_url(['page' => $page + 1])) ?>">Próxima página</a><?php endif; ?></div>
    <?php endif; ?>
</div>
<div class="hero-actions"><a class="btn ghost" href="index.php">Voltar ao painel principal</a></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>