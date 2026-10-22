<?php
/* WWAnalog — Página inicial: apresenta destaques e atalhos para o catálogo. */

// Define o título da página (aparece na aba do navegador)
$page_title = 'Início';

// Diz ao menu qual aba deve ficar destacada (a aba "Início")
$active = 'home';

// Carrega o topo do site (logo, menu, etc.)
require __DIR__ . '/includes/header.php';

// Vai no "estoque" e traz todas as obras cadastradas
$d = data_read();

// ===== ESCOLHENDO AS OBRAS EM DESTAQUE =====
// Prepara uma lista vazia que vai guardar as obras escolhidas para aparecer na home
$featured = [];

// Percorre a lista de "slugs" (apelidos/identificadores) das obras que queremos destacar.
// Slugs são nomes curtos usados na URL, tipo "notroad" em vez de "No Through Road".
foreach (['notroad', 'eventide', 'channel7'] as $slug) {

    // Para cada slug, percorre TODAS as obras do catálogo
    foreach ($d['works'] as $w) {

        // Se encontrar uma obra cujo slug seja igual ao que estamos procurando...
        if (($w['slug'] ?? '') === $slug) {

            // Adiciona essa obra na lista de destaques
            $featured[] = $w;

            // E para de procurar (break) — já achou essa, não precisa continuar
            break;
        }
    }
}

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho
ob_start();
?>

<!-- ===== BANNER PRINCIPAL (HERO) ===== -->
<section class="hero">
    <!-- Linha pequena acima do título (tipo "categoria" do banner) -->
    <div class="eyebrow">ARQUIVO INDEPENDENTE // ANALOG HORROR</div>

    <!-- Título grande do site -->
    <h1>WWAnalog</h1>

    <!-- Texto explicando o que é o site -->
    <p>Uma wiki para catalogar, organizar e descobrir obras, episódios, criadores e universos relacionados ao Analog Horror.</p>

    <!-- Botões de ação principais -->
    <div class="hero-actions">
        <a class="btn" href="<?= e(url('catalogo.php')) ?>">Explorar catálogo</a>
        <a class="btn ghost" href="<?= e(url('oquee.php')) ?>">Entender o gênero</a>
    </div>
</section>

<!-- ===== CABEÇALHO DA SEÇÃO DE DESTAQUES ===== -->
<div class="section-head">
    <div>
        <h2>Obras em destaque</h2>
        <small>Três entradas selecionadas do arquivo</small>
    </div>

    <!-- Botão para ver todas as obras (não só as 3 destacadas) -->
    <a class="btn ghost" href="<?= e(url('catalogo.php')) ?>">Ver catálogo completo</a>
</div>

<!-- ===== GRADE COM AS OBRAS EM DESTAQUE ===== -->
<div class="grid featured-grid">

    <!-- Para cada obra escolhida na lista $featured, cria um "cartão" clicável -->
    <?php foreach ($featured as $w): ?>

        <!-- O cartão inteiro é um link. O aria-label ajuda leitores de tela -->
        <a class="card card-link featured-card" href="<?= e(work_path($w)) ?>" aria-label="Abrir <?= e($w['title']) ?>">

            <!-- Área da capa: se tem imagem, mostra; se não, mostra "SEM CAPA" -->
            <div class="cover <?= e(cover_class($w)) ?>">
                <?php if (!empty($w['image'])): ?>
                    <!-- Se a imagem for um link completo (http/https), usa direto;
                         se for só caminho interno, completa com o endereço do site -->
                    <img src="<?= e(preg_match('~^https?://~i', $w['image']) ? $w['image'] : url($w['image'])) ?>"
                         alt="<?= e($w['title']) ?>" loading="lazy">
                <?php else: ?>
                    <span>SEM CAPA</span>
                <?php endif; ?>
            </div>

            <!-- Título da obra -->
            <h3><?= e($w['title']) ?></h3>

            <!-- Categoria da obra (ex.: Found Footage, Broadcast Hijack) -->
            <div class="meta"><?= e(category_name($w['category'])) ?></div>

            <!-- Descrição curta. Se não tiver, mostra um texto padrão -->
            <p class="desc"><?= e($w['description'] ?: 'Descrição ainda não cadastrada.') ?></p>

            <!-- Etiqueta/tag da obra -->
            <span class="tag"><?= e($w['tag']) ?></span>

            <!-- Bloco de segurança: classificação e aviso de conteúdo -->
            <div class="card-safety">
                <strong>Classificação: <?= e($w['rating'] ?? '14+') ?></strong>
                <span>
                    <?= e($w['warning'] ?? 'Conteúdo sensível: horror e imagens perturbadoras.') ?>
                </span>
            </div>

            <!-- Texto que indica que o cartão é clicável -->
            <div class="card-open">Abrir página do analog →</div>
        </a>
    <?php endforeach; ?>
</div>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML)
require __DIR__ . '/includes/footer.php';
?>