<?php
/* WWAnalog — Catálogo público: pesquisa, filtros e ordenação das obras cadastradas. */
$page_title = 'Catálogo';
$active = 'catalog';
require __DIR__ . '/includes/header.php';
$d = data_read();
$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['categoria'] ?? '');
$year = trim($_GET['ano'] ?? '');
$status = trim($_GET['status'] ?? '');
$sort = $_GET['ordem'] ?? 'az';
$items = array_filter($d['works'], function ($w) use ($q, $cat, $year, $status) {
    return (!$q || stripos($w['title'], $q) !== false) && (!$cat || $w['category'] === $cat) && (!$year || ($w['year'] ?? '') === $year) && (!$status || ($w['status'] ?? '') === $status);
});
usort($items, function ($a, $b) use ($sort) {
    $c = strcasecmp($a['title'], $b['title']);
    return $sort === 'za' ? -$c : $c;
});
$years = array_values(array_unique(array_filter(array_column($d['works'], 'year'))));
sort($years);
$statuses = array_values(array_unique(array_filter(array_column($d['works'], 'status'))));
ob_start(); ?>
<div class="breadcrumbs"><a href="<?= e(url('index.php')) ?>">WWAnalog</a> / Catálogo</div>
<div class="section-head">
    <div>
        <h1>Catálogo</h1><small><?= count($items) ?> registro(s) encontrado(s)</small>
    </div>
</div>
<form class="panel" method="get">
    <div class="filters">
        <input class="input" name="q" value="<?= e($q) ?>" placeholder="Buscar por título...">
        <select class="select" name="categoria">
            <option value="">Todas as categorias</option>
            <?php foreach ($d['categories'] as $c): ?><option value="<?= e($c['slug']) ?>" <?= $cat === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <select class="select" name="ano">
            <option value="">Todos os anos</option>
            <?php foreach ($years as $y): ?><option <?= $year === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?>
        </select><select class="select" name="status">
            <option value="">Todos os status</option>
            <?php foreach ($statuses as $s): ?><option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end"><select class="select" name="ordem" style="max-width:180px">
            <option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>A–Z</option>
            <option value="za" <?= $sort === 'za' ? 'selected' : '' ?>>Z–A</option>
        </select>
        <button class="btn">Aplicar filtros</button></div>
</form>
<div class="grid catalog-grid"><?php foreach ($items as $w): ?><a class="card card-link" href="<?= e(work_path($w)) ?>" aria-label="Abrir <?= e($w['title']) ?>">
            <div class="cover <?= e(cover_class($w)) ?>"><?php if (!empty($w['image'])): ?>
                <img src="<?= e(preg_match('~^https?://~i', $w['image']) ? $w['image'] : url($w['image'])) ?>" alt="<?= e($w['title']) ?>" loading="lazy"><?php else: ?>
                    <span>SEM CAPA</span>
                    <?php endif; ?>
                </div>
            <h3><?= e($w['title']) ?></h3>
            <div class="meta"><?= e(category_name($w['category'])) ?><?= ($w['year'] ?? '') ? ' · ' . e($w['year']) : '' ?></div>
            <p class="desc"><?= e($w['synopsis'] ?? $w['description'] ?: 'Sinopse ainda não cadastrada.') ?></p>
            <span class="tag"><?= e($w['tag']) ?></span>
            <div class="card-safety">
                <strong>Classificação indicativa: <?= e($w['rating'] ?? 'Não informada') ?></strong>
                <span>⚠ <?= e($w['warning'] ?? 'Conteúdo sensível: horror e imagens perturbadoras.') ?></span>
            </div>
            <div class="card-open">Abrir página do analog →</div>
        </a>
        <?php endforeach; ?>
    </div>
<?php if (!$items): ?>
    <div class="empty">Nenhuma obra corresponde aos filtros informados.</div>
    <?php endif; ?>
<?php $content = ob_get_clean();
shell($content, $active);
require __DIR__ . '/includes/footer.php'; 
?>