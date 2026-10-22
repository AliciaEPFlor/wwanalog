<?php
/* WWAnalog — Página inicial: apresenta destaques e atalhos para o catálogo. */
$page_title = 'Início';
$active = 'home';
require __DIR__ . '/includes/header.php';
$d = data_read();
$featured = [];
foreach (['notroad', 'eventide', 'channel7'] as $slug) {
    foreach ($d['works'] as $w) {
        if (($w['slug'] ?? '') === $slug) {
            $featured[] = $w;
            break;
        }
    }
}
ob_start(); ?>
<section class="hero">
    <div class="eyebrow">ARQUIVO INDEPENDENTE // ANALOG HORROR</div>
    <h1>WWAnalog</h1>
    <p>Uma wiki para catalogar, organizar e descobrir obras, episódios, criadores e universos relacionados ao Analog Horror.</p>
    <div class="hero-actions"><a class="btn" href="<?= e(url('catalogo.php')) ?>">Explorar catálogo</a><a class="btn ghost" href="<?= e(url('oquee.php')) ?>">Entender o gênero</a>
</div>
</section>
<div class="section-head">
    <div>
        <h2>Obras em destaque</h2><small>Três entradas selecionadas do arquivo</small>
    </div><a class="btn ghost" href="<?= e(url('catalogo.php')) ?>">Ver catálogo completo</a>
</div>
<div class="grid featured-grid">
    <?php foreach ($featured as $w): ?><a class="card card-link featured-card" href="<?= e(work_path($w)) ?>" aria-label="Abrir <?= e($w['title']) ?>">
            <div class="cover <?= e(cover_class($w)) ?>"><?php if (!empty($w['image'])): ?><img src="<?= e(preg_match('~^https?://~i', $w['image']) ? $w['image'] : url($w['image'])) ?>" alt="<?= e($w['title']) ?>" loading="lazy"><?php else: ?><span>SEM CAPA</span><?php endif; ?></div>
            <h3><?= e($w['title']) ?></h3>
            <div class="meta"><?= e(category_name($w['category'])) ?></div>
            <p class="desc"><?= e($w['description'] ?: 'Descrição ainda não cadastrada.') ?></p>
            <span class="tag"><?= e($w['tag']) ?></span>
            <div class="card-safety">
                <strong>Classificação: <?= e($w['rating'] ?? '14+') ?></strong>
                <span>
                    <?= e($w['warning'] ?? 'Conteúdo sensível: horror e imagens perturbadoras.') ?>
                </span></div>
            <div class="card-open">Abrir página do analog →</div>
        </a>
        <?php endforeach; ?>
    </div>
<?php $content = ob_get_clean();
shell($content, $active);
require __DIR__ . '/includes/footer.php'; ?>