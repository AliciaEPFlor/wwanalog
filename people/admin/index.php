<?php
/* WWAnalog — Painel inicial de administração e atalhos para ferramentas administrativas. */ require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Painel do desenvolvedor';
$active = 'admin';
require __DIR__ . '/../../includes/header.php';
$d = data_read();
$published = array_filter($d['comments'], fn($c) => ($c['status'] ?? 'published') === 'published');
$flagged = array_filter($d['comments'], fn($c) => in_array($c['status'] ?? 'published', ['flagged', 'pending', 'removed'], true));
$since = strtotime('-30 days');
$activeUsers = [];
foreach ($d['audit'] as $a)
    if (strtotime($a['at'] ?? '1970-01-01') >= $since && ($a['user_id'] ?? 0) > 0)
        $activeUsers[$a['user_id']] = true;
$views = array_filter($d['views'], fn($v) => strtotime($v['at'] ?? '1970-01-01') >= $since);
$counts = [];
foreach ($d['comments'] as $c)
    $counts[$c['work_id'] ?? 0] = ($counts[$c['work_id'] ?? 0] ?? 0) + 1;
$q = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterYear = trim($_GET['year'] ?? '');
$filteredWorks = array_values(array_filter($d['works'], function ($w) use ($q, $filterCategory, $filterStatus, $filterYear) {
    if ($q !== '' && stripos(($w['title'] ?? '') . ' ' . ($w['original_title'] ?? '') . ' ' . ($w['creator'] ?? ''), $q) === false)
        return false;
    if ($filterCategory !== '' && ($w['category'] ?? '') !== $filterCategory)
        return false;
    if ($filterStatus !== '' && ($w['status'] ?? '') !== $filterStatus)
        return false;
    if ($filterYear !== '' && (string) ($w['year'] ?? '') !== $filterYear)
        return false;
    return true;
})); ?>
<div class="breadcrumbs">Área restrita / Desenvolvedor</div>
<div class="notice"><strong>Atenção:</strong> esta tela e os formulários legados abaixo ainda leem/escrevem em
    <code>database/data.php</code>. Para cadastrar, editar e excluir dados diretamente no PostgreSQL, use o novo <a
        href="crud.php?entity=works">CRUD PostgreSQL</a>. Os filtros SQL ficam em <a href="postgres.php">Filtros do
        PostgreSQL</a>.</div>
<div class="section-head">
    <div>
        <div class="eyebrow">PAINEL RESTRITO</div>
        <h1>Painel do desenvolvedor</h1><small>Métricas calculadas somente a partir dos eventos e registros
            locais.</small>
    </div><a class="btn" href="crud.php?entity=works">+ Registrar obra no PostgreSQL</a>
</div>
<div class="grid">
    <div class="card">
        <h3>Total de ações registradas</h3><strong><?= count($d['audit']) ?></strong>
    </div>
    <div class="card">
        <h3>Usuários ativos (30 dias)</h3><strong><?= count($activeUsers) ?></strong>
    </div>
    <div class="card">
        <h3>Comentários registrados</h3><strong><?= count($d['comments']) ?></strong>
    </div>
    <div class="card">
        <h3>Comentários sinalizados / pendentes</h3><strong><?= count($flagged) ?></strong>
    </div>
    <div class="card">
        <h3>Visualizações registradas</h3><strong><?= count($views) ?></strong>
    </div>
    <div class="card">
        <h3>Conversas de auxílio</h3><strong><?= count($d['chat_logs']) ?></strong>
    </div>
</div>
<div class="panel" style="margin-top:22px">
    <h2>Obras — visualizar e editar</h2>
    <form method="get" class="filter-bar" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0"><input
            class="input" style="flex:1;min-width:190px" name="q" value="<?= e($q) ?>"
            placeholder="Buscar título, título original ou criador"><select class="select" name="category">
            <option value="">Todas as categorias</option>
            <?php foreach ($d['categories'] as $cat): ?>
                <option value="<?= e($cat['slug']) ?>" <?= $filterCategory === $cat['slug'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?></option><?php endforeach; ?>
        </select><select class="select" name="status">
            <option value="">Todas as situações</option>
            <?php foreach (['Em andamento', 'Concluída', 'Cancelada'] as $st): ?>
                <option value="<?= e($st) ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
        </select>
        <input class="input" style="width:120px" name="year" value="<?= e($filterYear) ?>" inputmode="numeric"
            placeholder="Ano"><button class="btn">Filtrar</button><a class="btn ghost"
            href="<?= e(url('people/admin/index.php')) ?>">Limpar</a>
    </form>
    <p class="small muted"><?= count($filteredWorks) ?> obra(s) encontrada(s).</p>
    <div class="table">
        <table class="table">
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Situação</th>
                <th>Visualizações</th>
                <th>Comentários</th>
                <th>Ação</th>
            </tr>
            <?php foreach ($filteredWorks as $w):
                $vc = count(array_filter($d['views'], fn($v) => (int) ($v['work_id'] ?? 0) === (int) $w['id'])); ?>
                <tr>
                    <td><?= e($w['title']) ?></td>
                    <td><?= e(category_name($w['category'])) ?></td>
                    <td><?= e($w['status'] ?? 'Não cadastrada') ?></td>
                    <td><?= $vc ?></td>
                    <td><?= $counts[$w['id']] ?? 0 ?></td>
                    <td><a class="btn ghost" href="crud.php?entity=works&amp;edit=<?= (int) $w['id'] ?>">Editar</a> <a
                            class="btn ghost" href="<?= e(url('obra.php?id=' . (int) $w['id'])) ?>">Visualizar</a></td>
                </tr><?php endforeach; ?>
        </table>
    </div>
</div>
<div class="two-col" style="margin-top:22px">
    <section class="panel">
        <h2>Ações recentes</h2><?php $recent = array_slice(array_reverse($d['audit']), 0, 12);
        if (!$recent): ?>
            <p class="empty">Nenhuma ação registrada ainda.</p>

        <?php else:
            foreach ($recent as $a): ?>
                <article class="comment">
                    <div class="eyebrow"><?= e($a['at'] ?? '') ?> · <?= e($a['action'] ?? 'ação') ?></div>
                    <p><?= e($a['email'] ?? '') ?> — <?= e($a['details'] ?? '') ?></p>
                </article>
            <?php
            endforeach;
        endif; ?>
    </section>
    <section class="panel">
        <h2>Comentários mais comentados por obra</h2>
        <?php arsort($counts);
        foreach (array_slice($counts, 0, 10, true) as $wid => $num):
            $w = find_work($wid);
            if ($w): ?>
                <div class="kv"><b><?= e($w['title']) ?></b><span><?= $num ?></span></div>
            <?php endif;
        endforeach; ?>
        <p class="small muted">A distribuição é calculada com os comentários armazenados. Não há gráfico temporal nesta
            versão sem biblioteca de gráficos.</p>
    </section>
</div>
<div class="hero-actions"><a class="btn" href="crud.php?entity=works">CRUD PostgreSQL (cadastrar / editar /
        excluir)</a><a class="btn ghost" href="postgres.php">Filtros SQL PostgreSQL</a><a class="btn ghost"
        href="comments.php">Moderação legada</a><a class="btn ghost" href="episodes.php">Episódios legados</a></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>