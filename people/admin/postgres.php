<?php
/* WWAnalog — Consulta administrativa PostgreSQL: aplica filtros, ordenação segura e paginação. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../../" porque este arquivo está dentro da subpasta people/admin/.
require_once __DIR__ . '/../../includes/functions.php';

// Exige que o usuário logado seja ADMINISTRADOR. Se não for, bloqueia o acesso.
require_admin();

// Define o título da página e marca "admin" como aba ativa do menu.
$page_title = 'Administração PostgreSQL';
$active = 'admin';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/../../includes/header.php';

// Função auxiliar que escapa texto para evitar XSS.
// Sempre que formos imprimir algo vindo do banco ou do usuário, passamos por aqui.
function pg_h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// ===== LISTA DE ENTIDADES PERMITIDAS =====
// Só aceita consultas nestas tabelas/módulos.
// Impede que alguém tente acessar uma tabela qualquer digitando o nome na URL.
$allowed = ['works', 'users', 'episodes', 'categories', 'tags', 'comments', 'ratings', 'user_lists', 'contact_requests', 'audit_logs'];

// Pega a entidade escolhida (via URL). Se não vier nada, usa "works".
// Se não estiver na lista permitida, volta para "works".
$entity = (string) ($_GET['entity'] ?? 'works');
if (!in_array($entity, $allowed, true))
    $entity = 'works';

// ===== CAPTURA DOS FILTROS (via URL) =====
$q           = trim((string) ($_GET['q'] ?? ''));            // Busca geral
$status      = trim((string) ($_GET['status'] ?? ''));       // Situação
$category    = trim((string) ($_GET['category'] ?? ''));     // Categoria (ou obra, em episódios)
$yearFrom    = trim((string) ($_GET['year_from'] ?? ''));    // Ano "de"
$yearTo      = trim((string) ($_GET['year_to'] ?? ''));      // Ano "até"
$score       = trim((string) ($_GET['score'] ?? ''));        // Nota (avaliações)
$dateFrom    = trim((string) ($_GET['date_from'] ?? ''));    // Data inicial
$dateTo      = trim((string) ($_GET['date_to'] ?? ''));      // Data final
$requestType = trim((string) ($_GET['request_type'] ?? '')); // Tipo (solicitações)
$role        = trim((string) ($_GET['role'] ?? ''));         // Papel (usuários)

// ===== PREPARAÇÃO DA PAGINAÇÃO =====
$rows = [];       // Vai guardar os registros retornados
$error = '';      // Mensagem de erro (se houver)
$total = 0;       // Total de registros encontrados
$page = max(1, (int) ($_GET['page'] ?? 1));  // Página atual (mínimo 1)
$perPage = 50;    // Quantos registros por página

// ===== LISTA DE COLUNAS PERMITIDAS PARA ORDENAÇÃO =====
// A ordenação usa uma "allowlist" para impedir que parâmetros da URL virem SQL arbitrário.
$sortMap = [
    'works'            => ['title', 'release_year', 'created_at'],
    'users'            => ['name', 'nickname', 'email', 'created_at'],
    'episodes'         => ['title', 'episode_number', 'release_date'],
    'categories'       => ['name', 'slug', 'created_at'],
    'tags'             => ['name', 'slug'],
    'comments'         => ['created_at', 'status'],
    'ratings'          => ['score', 'created_at'],
    'user_lists'       => ['list_status', 'created_at'],
    'contact_requests' => ['created_at', 'status'],
    'audit_logs'       => ['created_at', 'action']
];

// Pega a coluna de ordenação escolhida. Se não vier, usa a primeira da lista.
// Se não estiver na lista permitida, volta para a primeira.
$sort = (string) ($_GET['sort'] ?? ($sortMap[$entity][0] ?? 'created_at'));
if (!in_array($sort, $sortMap[$entity], true))
    $sort = $sortMap[$entity][0];

// ===== MAPA DE COLUNAS PARA SQL QUALIFICADO =====
// Traduz o nome amigável da coluna para o nome qualificado (com prefixo da tabela).
// Isso evita ambiguidade quando há JOIN entre tabelas.
$sortSqlMap = [
    'works'            => ['title' => 'w.title', 'release_year' => 'w.release_year', 'created_at' => 'w.created_at'],
    'users'            => ['name' => 'u.name', 'nickname' => 'u.nickname', 'email' => 'u.email', 'created_at' => 'u.created_at'],
    'episodes'         => ['title' => 'e.title', 'episode_number' => 'e.episode_number', 'release_date' => 'e.release_date'],
    'categories'       => ['name' => 'name', 'slug' => 'slug', 'created_at' => 'created_at'],
    'tags'             => ['name' => 'name', 'slug' => 'slug'],
    'comments'         => ['created_at' => 'c.created_at', 'status' => 'c.status'],
    'ratings'          => ['score' => 'r.score', 'created_at' => 'r.created_at'],
    'user_lists'       => ['list_status' => 'ul.list_status', 'created_at' => 'ul.created_at'],
    'contact_requests' => ['created_at' => 'created_at', 'status' => 'status'],
    'audit_logs'       => ['created_at' => 'created_at', 'action' => 'action']
];

// Direção da ordenação: "asc" (crescente) ou "desc" (decrescente).
// Qualquer outro valor cai no padrão "DESC".
$direction = strtolower((string) ($_GET['direction'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

// ===== CONEXÃO E CONSULTA =====
try {
    // Abre a conexão com o PostgreSQL.
    $pdo = db();

    // Carrega listas auxiliares para os menus (categorias e papéis).
    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    $roles      = $pdo->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();

    // ===== CONSTRUÇÃO DAS CONDIÇÕES (WHERE) =====
    // Condições e valores ficam separados para evitar concatenar dados do usuário no SQL.
    $where = [];    // Lista de condições SQL
    $params = [];   // Lista de valores para os "?"

    // Função interna que adiciona uma condição de busca "ILIKE" (sem diferenciar maiúsculas).
    $like = function (string $expr, string $value) use (&$where, &$params) {
        $where[] = "$expr ILIKE ?";
        $params[] = '%' . $value . '%';
    };

    // ===== CONSULTA ESPECÍFICA DE CADA ENTIDADE =====
    // Cada entidade monta sua própria consulta e adiciona condições somente quando há filtros.
    switch ($entity) {

        // ---- OBRAS ----
        case 'works':
            // Busca textual no título + criador + descrição.
            if ($q !== '')
                $like("(w.title || ' ' || COALESCE(w.creator,'') || ' ' || COALESCE(w.description,''))", $q);

            // Filtro de situação.
            if ($status !== '') {
                $where[] = 'w.status = ?';
                $params[] = $status;
            }

            // Filtro de categoria (verifica se a obra está vinculada a essa categoria).
            if ($category !== '') {
                $where[] = 'EXISTS (SELECT 1 FROM work_categories wc WHERE wc.work_id=w.id AND wc.category_id=?)';
                $params[] = (int) $category;
            }

            // Faixa de anos: "de" e "até".
            if ($yearFrom !== '' && ctype_digit($yearFrom)) {
                $where[] = 'w.release_year >= ?';
                $params[] = (int) $yearFrom;
            }
            if ($yearTo !== '' && ctype_digit($yearTo)) {
                $where[] = 'w.release_year <= ?';
                $params[] = (int) $yearTo;
            }

            // Consulta base: seleciona os campos da obra + agrega as categorias numa string.
            $select = "SELECT w.id,w.title,w.slug,w.creator,w.release_year,w.status,w.classification,w.created_at,
                (SELECT string_agg(c.name, ', ' ORDER BY c.name) FROM work_categories wc JOIN categories c ON c.id=wc.category_id WHERE wc.work_id=w.id) AS categories
                FROM works w";

            // Mapeamento de colunas exibidas: rótulo => campo.
            $columns = ['ID' => 'id', 'Título' => 'title', 'Criador' => 'creator', 'Ano' => 'release_year', 'Situação' => 'status', 'Classificação' => 'classification', 'Categoria(s)' => 'categories', 'Criada em' => 'created_at'];
            break;

        // ---- USUÁRIOS ----
        case 'users':
            // Busca textual no nome + apelido + e-mail.
            if ($q !== '')
                $like("(u.name || ' ' || u.nickname || ' ' || u.email)", $q);

            // Filtros de situação, papel e datas de cadastro.
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
                // Inclui o dia inteiro da data final.
                $where[] = "u.created_at < (?::date + INTERVAL '1 day')";
                $params[] = $dateTo;
            }

            $select = "SELECT u.id,u.name,u.nickname,u.email,u.status,r.name AS role,u.created_at FROM users u LEFT JOIN roles r ON r.id=u.role_id";
            $columns = ['ID' => 'id', 'Nome' => 'name', 'Apelido' => 'nickname', 'E-mail' => 'email', 'Papel' => 'role', 'Situação' => 'status', 'Cadastro' => 'created_at'];
            break;

        // ---- EPISÓDIOS ----
        case 'episodes':
            // Busca textual no título + descrição + título da obra.
            if ($q !== '')
                $like("(e.title || ' ' || COALESCE(e.description,'') || ' ' || w.title)", $q);

            // Filtro por obra (reutiliza o campo "category" no formulário).
            if ($category !== '') {
                $where[] = 'e.work_id=?';
                $params[] = (int) $category;
            }

            // Faixa de datas de lançamento.
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

        // ---- CATEGORIAS ----
        case 'categories':
            if ($q !== '')
                $like("(name || ' ' || slug || ' ' || COALESCE(description,''))", $q);

            $select = "SELECT id,name,slug,description,created_at FROM categories";
            $columns = ['ID' => 'id', 'Nome' => 'name', 'Slug' => 'slug', 'Descrição' => 'description', 'Criada em' => 'created_at'];
            break;

        // ---- TAGS ----
        case 'tags':
            if ($q !== '')
                $like("(name || ' ' || slug)", $q);

            $select = "SELECT id,name,slug FROM tags";
            $columns = ['ID' => 'id', 'Nome' => 'name', 'Slug' => 'slug'];
            break;

        // ---- COMENTÁRIOS ----
        case 'comments':
            // Busca textual no conteúdo + apelido do autor + título da obra.
            if ($q !== '')
                $like("(c.content || ' ' || u.nickname || ' ' || w.title)", $q);

            // Filtros de situação e datas.
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

        // ---- AVALIAÇÕES ----
        case 'ratings':
            if ($q !== '')
                $like("(u.nickname || ' ' || w.title)", $q);

            // Filtro de nota (1 a 5).
            if ($score !== '' && ctype_digit($score) && (int) $score >= 1 && (int) $score <= 5) {
                $where[] = 'r.score=?';
                $params[] = (int) $score;
            }

            // Filtros de datas.
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

        // ---- LISTAS PESSOAIS ----
        case 'user_lists':
            if ($q !== '')
                $like("(u.nickname || ' ' || w.title)", $q);

            // Filtro de estado da lista.
            if ($status !== '') {
                $where[] = 'ul.list_status=?';
                $params[] = $status;
            }

            $select = "SELECT ul.id,u.nickname,w.title AS work_title,ul.list_status,ul.created_at FROM user_lists ul JOIN users u ON u.id=ul.user_id JOIN works w ON w.id=ul.work_id";
            $columns = ['ID' => 'id', 'Usuário' => 'nickname', 'Obra' => 'work_title', 'Estado' => 'list_status', 'Adicionada em' => 'created_at'];
            break;

        // ---- SOLICITAÇÕES DE CONTATO ----
        case 'contact_requests':
            if ($q !== '')
                $like("(COALESCE(name,'') || ' ' || COALESCE(email,'') || ' ' || subject || ' ' || message)", $q);

            // Filtros de situação, tipo e datas.
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

        // ---- AUDITORIA ----
        case 'audit_logs':
            if ($q !== '')
                $like("(action || ' ' || COALESCE(entity_type,'') || ' ' || COALESCE(details::text,''))", $q);

            // Filtros de datas.
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

    // ===== MONTA O WHERE FINAL =====
    // Se houver condições, junta com "AND"; senão, fica vazio.
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    // ===== CONTA O TOTAL DE REGISTROS =====
    // Envolve a consulta numa subquery para contar o total (para paginação).
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM (" . $select . $whereSql . ") filtered_rows");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // ===== EXECUTA A CONSULTA COM PAGINAÇÃO =====
    $offset = ($page - 1) * $perPage;

    // Monta a consulta final com ORDER BY, LIMIT e OFFSET.
    // A ordenação usa o mapa qualificado (seguro) e a direção é sempre ASC ou DESC.
    $stmt = $pdo->prepare(
        $select . $whereSql .
        " ORDER BY " . $sortSqlMap[$entity][$sort] . " " . $direction .
        " LIMIT " . $perPage . " OFFSET " . $offset
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

} catch (Throwable $e) {
    // Se algo der errado, mostra uma mensagem amigável.
    $error = 'Não foi possível consultar o PostgreSQL. Confira database/config.local.php, a extensão pdo_pgsql e se as 13 tabelas já foram criadas. Consulte o log do servidor para detalhes técnicos.';
}

// ===== FUNÇÃO AUXILIAR DE URL =====
// Gera uma URL preservando os filtros atuais, mas substituindo alguns parâmetros.
// Usada nos botões "Página anterior" e "Próxima página".
function pg_query_url(array $replace = []): string
{
    // Junta os parâmetros atuais (GET) com os que serão substituídos.
    $params = array_merge($_GET, $replace);

    // Remove parâmetros vazios ou nulos.
    foreach ($params as $k => $v)
        if ($v === '' || $v === null)
            unset($params[$k]);

    // Monta a URL final.
    return 'postgres.php?' . http_build_query($params);
}
?>

<!-- Migalhas de pão -->
<div class="breadcrumbs">Área restrita / Administração / PostgreSQL</div>

<!-- ===== CABEÇALHO DA PÁGINA ===== -->
<div class="section-head">
    <div>
        <div class="eyebrow">DADOS RELACIONAIS</div>
        <h1>Filtros do PostgreSQL</h1>
        <p class="small muted">Consultas SQL de leitura com busca, filtros, ordenação e paginação. As telas antigas
            ainda usam database/data.php.</p>
    </div>
    <!-- Atalho para o CRUD PostgreSQL -->
    <a class="btn" href="crud.php?entity=works">Abrir CRUD PostgreSQL</a>
</div>

<div class="panel">

    <!-- ===== FORMULÁRIO DE FILTROS ===== -->
    <form method="get" class="filter-bar" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0;align-items:end">

        <!-- Campo: escolha da tabela/módulo -->
        <label> Tabela / módulo
            <select class="select" name="entity">
                <?php foreach (['works' => 'Obras', 'users' => 'Usuários', 'episodes' => 'Episódios', 'categories' => 'Categorias', 'tags' => 'Tags', 'comments' => 'Comentários', 'ratings' => 'Avaliações', 'user_lists' => 'Listas pessoais', 'contact_requests' => 'Solicitações', 'audit_logs' => 'Auditoria'] as $key => $label): ?>
                    <option value="<?= pg_h($key) ?>" <?= $entity === $key ? 'selected' : '' ?>><?= pg_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <!-- Campo: busca geral -->
        <label>Busca geral
            <input class="input" name="q" value="<?= pg_h($q) ?>" placeholder="Título, nome, conteúdo...">
        </label>

        <!-- Campo de situação (aparece só para algumas entidades) -->
        <?php if (in_array($entity, ['works', 'users', 'comments', 'user_lists', 'contact_requests'], true)): ?>
            <label>Situação
                <select class="select" name="status">
                    <option value="">Todas</option>
                    <?php
                    // Define as opções de situação conforme a entidade.
                    $opts = $entity === 'users'
                        ? ['ativo', 'suspenso', 'inativo']
                        : ($entity === 'comments'
                            ? ['publicado', 'oculto', 'removido']
                            : ($entity === 'user_lists'
                                ? ['minha_lista', 'assistindo', 'assistido']
                                : ($entity === 'contact_requests'
                                    ? ['pendente', 'em_analise', 'resolvida', 'rejeitada']
                                    : ['Em andamento', 'Concluída', 'Cancelada'])));
                    foreach ($opts as $op): ?>
                        <option value="<?= pg_h($op) ?>" <?= $status === $op ? 'selected' : '' ?>><?= pg_h($op) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <!-- Campos específicos de OBRAS: categoria + faixa de anos -->
        <?php if ($entity === 'works'): ?>
            <label>Categoria
                <select class="select" name="category">
                    <option value="">Todas</option>
                    <?php foreach (($categories ?? []) as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (string) $category === (string) $c['id'] ? 'selected' : '' ?>>
                            <?= pg_h($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Ano de
                <input class="input" type="number" min="1800" max="2200" name="year_from" value="<?= pg_h($yearFrom) ?>" placeholder="De">
            </label>
            <label>Ano até
                <input class="input" type="number" min="1800" max="2200" name="year_to" value="<?= pg_h($yearTo) ?>" placeholder="Até">
            </label>
        <?php endif; ?>

        <!-- Campo específico de USUÁRIOS: papel -->
        <?php if ($entity === 'users'): ?>
            <label>Papel
                <select class="select" name="role">
                    <option value="">Todos</option>
                    <?php foreach (($roles ?? []) as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (string) $role === (string) $r['id'] ? 'selected' : '' ?>>
                            <?= pg_h($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <!-- Campo específico de EPISÓDIOS: obra -->
        <?php if ($entity === 'episodes'): ?>
            <label>Obra
                <select class="select" name="category">
                    <option value="">Todas</option>
                    <?php foreach (($pdo->query('SELECT id,title FROM works ORDER BY title')->fetchAll() ?? []) as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= (string) $category === (string) $w['id'] ? 'selected' : '' ?>>
                            <?= pg_h($w['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <!-- Campo específico de AVALIAÇÕES: nota -->
        <?php if ($entity === 'ratings'): ?>
            <label>Nota
                <select class="select" name="score">
                    <option value="">Todas</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= $score === (string) $i ? 'selected' : '' ?>><?= $i ?> estrela(s)</option>
                    <?php endfor; ?>
                </select>
            </label>
        <?php endif; ?>

        <!-- Campo específico de SOLICITAÇÕES: tipo -->
        <?php if ($entity === 'contact_requests'): ?>
            <label>Tipo
                <select class="select" name="request_type">
                    <option value="">Todos</option>
                    <?php foreach (['correcao', 'atualizacao', 'remocao', 'reclamacao', 'sugestao', 'outro'] as $op): ?>
                        <option value="<?= $op ?>" <?= $requestType === $op ? 'selected' : '' ?>><?= pg_h($op) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <!-- Campos de data (aparecem para várias entidades) -->
        <?php if (in_array($entity, ['users', 'episodes', 'comments', 'ratings', 'contact_requests', 'audit_logs'], true)): ?>
            <label>Data inicial
                <input class="input" type="date" name="date_from" value="<?= pg_h($dateFrom) ?>">
            </label>
            <label>Data final
                <input class="input" type="date" name="date_to" value="<?= pg_h($dateTo) ?>">
            </label>
        <?php endif; ?>

        <!-- Campo: coluna de ordenação (opções vêm do $sortMap) -->
        <label>Ordenar por
            <select class="select" name="sort">
                <?php foreach ($sortMap[$entity] as $col): ?>
                    <option value="<?= pg_h($col) ?>" <?= $sort === $col ? 'selected' : '' ?>><?= pg_h($col) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <!-- Campo: direção da ordenação -->
        <label>Direção
            <select class="select" name="direction">
                <option value="desc" <?= $direction === 'DESC' ? 'selected' : '' ?>>Decrescente</option>
                <option value="asc" <?= $direction === 'ASC' ? 'selected' : '' ?>>Crescente</option>
            </select>
        </label>

        <!-- Botões: aplicar filtros e limpar -->
        <button class="btn">Filtrar</button>
        <a class="btn ghost" href="postgres.php?entity=<?= pg_h($entity) ?>">Limpar</a>
    </form>

    <!-- ===== RESULTADO DA CONSULTA ===== -->
    <?php if ($error): ?>
        <!-- Se houve erro, mostra a mensagem -->
        <div class="notice"><?= pg_h($error) ?></div>
    <?php else: ?>

        <!-- Quantos registros foram encontrados e quantos são exibidos por página -->
        <p class="small muted">
            <?= number_format($total, 0, ',', '.') ?> registro(s) encontrado(s). Exibindo até <?= $perPage ?> por página.
        </p>

        <!-- ===== TABELA DE RESULTADOS ===== -->
        <div class="table">
            <table class="table">

                <!-- Cabeçalho: rótulos amigáveis das colunas -->
                <thead>
                    <tr>
                        <?php foreach ($columns as $label => $key): ?>
                            <th><?= pg_h($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$rows): ?>
                        <!-- Se não houver registros, mostra mensagem -->
                        <tr>
                            <td colspan="<?= count($columns) ?>">Nenhum registro encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php
                                // Percorre as colunas configuradas e imprime o valor correspondente.
                                foreach ($columns as $label => $key):
                                    $value = $row[$key] ?? '';

                                    // Se o valor for array/objeto, converte para JSON.
                                    if (is_array($value) || is_object($value))
                                        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                ?>
                                    <td><?= pg_h(is_scalar($value) ? $value : '') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ===== BOTÕES DE PAGINAÇÃO ===== -->
        <div class="hero-actions" style="margin-top:14px">
            <?php if ($page > 1): ?>
                <a class="btn ghost" href="<?= pg_h(pg_query_url(['page' => $page - 1])) ?>">Página anterior</a>
            <?php endif; ?>

            <?php if ($page * $perPage < $total): ?>
                <a class="btn ghost" href="<?= pg_h(pg_query_url(['page' => $page + 1])) ?>">Próxima página</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Botão para voltar ao painel principal -->
<div class="hero-actions">
    <a class="btn ghost" href="index.php">Voltar ao painel principal</a>
</div>

<?php
// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__ . '/../../includes/footer.php';
?>