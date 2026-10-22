<?php
/* WWAnalog — Painel inicial de administração e atalhos para ferramentas administrativas. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../../" porque este arquivo está dentro da subpasta people/admin/.
require_once __DIR__ . '/../../includes/functions.php';

// Exige que o usuário logado seja ADMINISTRADOR. Se não for, bloqueia o acesso.
require_admin();

// Define o título da página e marca "admin" como aba ativa do menu.
$page_title = 'Painel do desenvolvedor';
$active = 'admin';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/../../includes/header.php';

// Vai no "estoque" (arquivo de dados) e traz todos os dados.
$d = data_read();

// ===== CONTAGENS E MÉTRICAS =====

// Comentários com status "published" (publicados).
$published = array_filter($d['comments'], fn($c) => ($c['status'] ?? 'published') === 'published');

// Comentários sinalizados, pendentes ou removidos (precisam de atenção).
$flagged = array_filter($d['comments'], fn($c) => in_array($c['status'] ?? 'published', ['flagged', 'pending', 'removed'], true));

// Data de corte: 30 dias atrás (para calcular "usuários ativos nos últimos 30 dias").
$since = strtotime('-30 days');

// Prepara um array para guardar os IDs de usuários ativos.
$activeUsers = [];

// Percorre o histórico de auditoria e marca os usuários que tiveram ações nos últimos 30 dias.
foreach ($d['audit'] as $a)
    if (strtotime($a['at'] ?? '1970-01-01') >= $since && ($a['user_id'] ?? 0) > 0)
        $activeUsers[$a['user_id']] = true;

// Visualizações registradas nos últimos 30 dias.
$views = array_filter($d['views'], fn($v) => strtotime($v['at'] ?? '1970-01-01') >= $since);

// Prepara um array para contar quantos comentários cada obra tem.
$counts = [];
foreach ($d['comments'] as $c)
    $counts[$c['work_id'] ?? 0] = ($counts[$c['work_id'] ?? 0] ?? 0) + 1;

// ===== FILTROS DA LISTA DE OBRAS =====

// Lê os filtros escolhidos (texto, categoria, situação, ano) via URL.
$q = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterYear = trim($_GET['year'] ?? '');

// Filtra a lista de obras aplicando os critérios escolhidos.
// Cada condição só restringe se o filtro estiver preenchido.
$filteredWorks = array_values(array_filter($d['works'], function ($w) use ($q, $filterCategory, $filterStatus, $filterYear) {

    // Filtro 1: busca no título, título original ou criador.
    if ($q !== '' && stripos(($w['title'] ?? '') . ' ' . ($w['original_title'] ?? '') . ' ' . ($w['creator'] ?? ''), $q) === false)
        return false;

    // Filtro 2: categoria.
    if ($filterCategory !== '' && ($w['category'] ?? '') !== $filterCategory)
        return false;

    // Filtro 3: situação.
    if ($filterStatus !== '' && ($w['status'] ?? '') !== $filterStatus)
        return false;

    // Filtro 4: ano.
    if ($filterYear !== '' && (string) ($w['year'] ?? '') !== $filterYear)
        return false;

    // Se passou por todos os filtros, mantém a obra.
    return true;
}));
?>

<!-- Migalhas de pão: mostra o caminho (Área restrita / Desenvolvedor) -->
<div class="breadcrumbs">Área restrita / Desenvolvedor</div>

<!-- Aviso explicando que esta tela ainda usa o armazenamento legado (data.php)
     e indicando os links para o CRUD novo em PostgreSQL -->
<div class="notice">
    <strong>Atenção:</strong> esta tela e os formulários legados abaixo ainda leem/escrevem em
    <code>database/data.php</code>. Para cadastrar, editar e excluir dados diretamente no PostgreSQL, use o novo
    <a href="crud.php?entity=works">CRUD PostgreSQL</a>. Os filtros SQL ficam em
    <a href="postgres.php">Filtros do PostgreSQL</a>.
</div>

<!-- ===== CABEÇALHO DA PÁGINA ===== -->
<div class="section-head">
    <div>
        <div class="eyebrow">PAINEL RESTRITO</div>
        <h1>Painel do desenvolvedor</h1>
        <small>Métricas calculadas somente a partir dos eventos e registros locais.</small>
    </div>
    <!-- Botão de atalho para cadastrar uma obra no PostgreSQL -->
    <a class="btn" href="crud.php?entity=works">+ Registrar obra no PostgreSQL</a>
</div>

<!-- ===== GRADE DE MÉTRICAS (6 cartões) ===== -->
<div class="grid">

    <!-- Cartão 1: total de ações registradas na auditoria -->
    <div class="card">
        <h3>Total de ações registradas</h3><strong><?= count($d['audit']) ?></strong>
    </div>

    <!-- Cartão 2: usuários que tiveram ações nos últimos 30 dias -->
    <div class="card">
        <h3>Usuários ativos (30 dias)</h3><strong><?= count($activeUsers) ?></strong>
    </div>

    <!-- Cartão 3: total de comentários registrados -->
    <div class="card">
        <h3>Comentários registrados</h3><strong><?= count($d['comments']) ?></strong>
    </div>

    <!-- Cartão 4: comentários sinalizados/pendentes/removidos -->
    <div class="card">
        <h3>Comentários sinalizados / pendentes</h3><strong><?= count($flagged) ?></strong>
    </div>

    <!-- Cartão 5: visualizações nos últimos 30 dias -->
    <div class="card">
        <h3>Visualizações registradas</h3><strong><?= count($views) ?></strong>
    </div>

    <!-- Cartão 6: total de conversas com a IA -->
    <div class="card">
        <h3>Conversas de auxílio</h3><strong><?= count($d['chat_logs']) ?></strong>
    </div>
</div>

<!-- ===== PAINEL: LISTA DE OBRAS COM FILTROS ===== -->
<div class="panel" style="margin-top:22px">
    <h2>Obras — visualizar e editar</h2>

    <!-- Formulário de filtros: busca textual, categoria, situação e ano -->
    <form method="get" class="filter-bar" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0">

        <!-- Campo: busca por título, título original ou criador -->
        <input class="input" style="flex:1;min-width:190px" name="q" value="<?= e($q) ?>"
               placeholder="Buscar título, título original ou criador">

        <!-- Campo: filtro por categoria -->
        <select class="select" name="category">
            <option value="">Todas as categorias</option>
            <?php foreach ($d['categories'] as $cat): ?>
                <option value="<?= e($cat['slug']) ?>" <?= $filterCategory === $cat['slug'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Campo: filtro por situação -->
        <select class="select" name="status">
            <option value="">Todas as situações</option>
            <?php foreach (['Em andamento', 'Concluída', 'Cancelada'] as $st): ?>
                <option value="<?= e($st) ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Campo: filtro por ano -->
        <input class="input" style="width:120px" name="year" value="<?= e($filterYear) ?>" inputmode="numeric" placeholder="Ano">

        <!-- Botões: aplicar filtros e limpar -->
        <button class="btn">Filtrar</button>
        <a class="btn ghost" href="<?= e(url('people/admin/index.php')) ?>">Limpar</a>
    </form>

    <!-- Quantas obras foram encontradas com os filtros atuais -->
    <p class="small muted"><?= count($filteredWorks) ?> obra(s) encontrada(s).</p>

    <!-- ===== TABELA DE OBRAS ===== -->
    <div class="table">
        <table class="table">

            <!-- Cabeçalho da tabela -->
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Situação</th>
                <th>Visualizações</th>
                <th>Comentários</th>
                <th>Ação</th>
            </tr>

            <?php
            // Percorre cada obra filtrada.
            foreach ($filteredWorks as $w):

                // Conta quantas visualizações essa obra teve.
                $vc = count(array_filter($d['views'], fn($v) => (int) ($v['work_id'] ?? 0) === (int) $w['id']));
            ?>
                <tr>
                    <td><?= e($w['title']) ?></td>
                    <td><?= e(category_name($w['category'])) ?></td>
                    <td><?= e($w['status'] ?? 'Não cadastrada') ?></td>
                    <td><?= $vc ?></td>
                    <td><?= $counts[$w['id']] ?? 0 ?></td>
                    <td>
                        <!-- Botão: editar no CRUD do PostgreSQL -->
                        <a class="btn ghost" href="crud.php?entity=works&amp;edit=<?= (int) $w['id'] ?>">Editar</a>

                        <!-- Botão: visualizar a página pública da obra -->
                        <a class="btn ghost" href="<?= e(url('obra.php?id=' . (int) $w['id'])) ?>">Visualizar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<!-- ===== DUAS COLUNAS: AÇÕES RECENTES e COMENTÁRIOS POR OBRA ===== -->
<div class="two-col" style="margin-top:22px">

    <!-- ===== COLUNA 1: AÇÕES RECENTES (últimas 12) ===== -->
    <section class="panel">
        <h2>Ações recentes</h2>

        <?php
        // Pega as últimas 12 ações da auditoria, do mais recente para o mais antigo.
        $recent = array_slice(array_reverse($d['audit']), 0, 12);

        // Se não houver nenhuma ação registrada...
        if (!$recent):
        ?>
            <p class="empty">Nenhuma ação registrada ainda.</p>

        <?php
        // Se houver, mostra cada uma num bloco tipo "comentário".
        else:
            foreach ($recent as $a):
        ?>
                <article class="comment">
                    <!-- Data/hora e tipo da ação -->
                    <div class="eyebrow"><?= e($a['at'] ?? '') ?> · <?= e($a['action'] ?? 'ação') ?></div>

                    <!-- E-mail do usuário e detalhes da ação -->
                    <p><?= e($a['email'] ?? '') ?> — <?= e($a['details'] ?? '') ?></p>
                </article>
            <?php
            endforeach;
        endif;
        ?>
    </section>

    <!-- ===== COLUNA 2: TOP 10 OBRAS COM MAIS COMENTÁRIOS ===== -->
    <section class="panel">
        <h2>Comentários mais comentados por obra</h2>

        <?php
        // Ordena a contagem de comentários do maior para o menor.
        arsort($counts);

        // Pega as 10 obras com mais comentários (mantendo a chave = ID da obra).
        foreach (array_slice($counts, 0, 10, true) as $wid => $num):

            // Busca a obra pelo ID para pegar o título.
            $w = find_work($wid);

            // Se a obra ainda existe, mostra o título e a quantidade.
            if ($w):
        ?>
                <div class="kv">
                    <b><?= e($w['title']) ?></b>
                    <span><?= $num ?></span>
                </div>
            <?php
            endif;
        endforeach;
        ?>

        <!-- Aviso explicando que não há gráfico nesta versão -->
        <p class="small muted">A distribuição é calculada com os comentários armazenados. Não há gráfico temporal nesta versão sem biblioteca de gráficos.</p>
    </section>
</div>

<!-- ===== BOTÕES DE ATALHO PARA AS FERRAMENTAS ADMINISTRATIVAS ===== -->
<div class="hero-actions">
    <a class="btn" href="crud.php?entity=works">CRUD PostgreSQL (cadastrar / editar / excluir)</a>
    <a class="btn ghost" href="postgres.php">Filtros SQL PostgreSQL</a>
    <a class="btn ghost" href="comments.php">Moderação legada</a>
    <a class="btn ghost" href="episodes.php">Episódios legados</a>
</div>

<?php
// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__ . '/../../includes/footer.php';
?>