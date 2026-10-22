<?php
/* WWAnalog — Catálogo público: pesquisa, filtros e ordenação das obras cadastradas. */

// Dá um nome para a página (aparece na aba do navegador)
$page_title = 'Catálogo';

// Diz ao menu qual aba deve ficar destacada (a aba "Catálogo")
$active = 'catalog';

// Carrega o topo do site (logo, menu, etc.)
require __DIR__ . '/includes/header.php';

// Vai no "estoque" e traz todas as obras cadastradas de uma vez
$d = data_read();

// Lê o que o visitante digitou/escolheu nos filtros.
// O "?? ''" significa: se ele não escolheu nada, deixa em branco (evita erro).
$q      = trim($_GET['q'] ?? '');          // Texto digitado na busca
$cat    = trim($_GET['categoria'] ?? '');  // Categoria escolhida
$year   = trim($_GET['ano'] ?? '');        // Ano escolhido
$status = trim($_GET['status'] ?? '');     // Status escolhido
$sort   = $_GET['ordem'] ?? 'az';          // Ordem: A–Z (padrão) ou Z–A

// Percorre TODAS as obras e mantém só aquelas que batem com TODOS os filtros.
// Cada linha é uma "peneira":
//   - Se não digitou busca, deixa passar todo mundo no título
//   - Se não escolheu categoria, deixa passar todo mundo na categoria
//   - E assim por diante para ano e status
$items = array_filter($d['works'], function ($w) use ($q, $cat, $year, $status) {
    return (!$q      || stripos($w['title'], $q) !== false)   // Título contém a busca? (ignora maiúscula/minúscula)
        && (!$cat    || $w['category'] === $cat)              // Categoria é igual à escolhida?
        && (!$year   || ($w['year'] ?? '') === $year)         // Ano é igual ao escolhido?
        && (!$status || ($w['status'] ?? '') === $status);    // Status é igual ao escolhido?
});

// Organiza as obras em ordem alfabética pelo título.
// Se o visitante escolheu "Z–A", o sinal de menos inverte a ordem.
usort($items, function ($a, $b) use ($sort) {
    $c = strcasecmp($a['title'], $b['title']);
    return $sort === 'za' ? -$c : $c;
});

// Monta a lista de ANOS que aparecem no menu suspenso:
// pega só o campo "ano" de cada obra, tira os vazios, tira os repetidos e ordena.
$years = array_values(array_unique(array_filter(array_column($d['works'], 'year'))));
sort($years);

// Faz a mesma coisa para os STATUS (lista sem repetições para o menu suspenso).
$statuses = array_values(array_unique(array_filter(array_column($d['works'], 'status'))));

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho.
// No final, esse rascunho será encaixado dentro do layout do site.
ob_start();
?>

<!-- Migalhas de pão: mostra o caminho de navegação (Home / Catálogo) -->
<div class="breadcrumbs"><a href="<?= e(url('index.php')) ?>">WWAnalog</a> / Catálogo</div>

<!-- Cabeçalho da seção: título e quantas obras foram encontradas -->
<div class="section-head">
    <div>
        <h1>Catálogo</h1><small><?= count($items) ?> registro(s) encontrado(s)</small>
    </div>
</div>

<!-- Formulário de filtros. Usa "get" para que os filtros fiquem na URL
     (assim o visitante pode copiar o link e compartilhar) -->
<form class="panel" method="get">
    <div class="filters">
        <!-- Campo de busca por título. Mostra o que o visitante já digitou -->
        <input class="input" name="q" value="<?= e($q) ?>" placeholder="Buscar por título...">

        <!-- Menu de categorias. Marca como "selected" a categoria já escolhida -->
        <select class="select" name="categoria">
            <option value="">Todas as categorias</option>
            <?php foreach ($d['categories'] as $c): ?>
                <option value="<?= e($c['slug']) ?>" <?= $cat === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Menu de anos. As opções vêm da lista $years montada acima -->
        <select class="select" name="ano">
            <option value="">Todos os anos</option>
            <?php foreach ($years as $y): ?>
                <option <?= $year === $y ? 'selected' : '' ?>><?= e($y) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Menu de status. As opções vêm da lista $statuses montada acima -->
        <select class="select" name="status">
            <option value="">Todos os status</option>
            <?php foreach ($statuses as $s): ?>
                <option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Área da direita: escolha da ordem (A–Z ou Z–A) e botão para aplicar os filtros -->
    <div style="display:flex;gap:8px;justify-content:flex-end">
        <select class="select" name="ordem" style="max-width:180px">
            <option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>A–Z</option>
            <option value="za" <?= $sort === 'za' ? 'selected' : '' ?>>Z–A</option>
        </select>
        <button class="btn">Aplicar filtros</button>
    </div>
</form>

<!-- Grade de cards: cada obra vira um "cartão" clicável que leva para a página dela -->
<div class="grid catalog-grid">
    <?php foreach ($items as $w): ?>
        <!-- O cartão inteiro é um link. O aria-label ajuda leitores de tela (acessibilidade) -->
        <a class="card card-link" href="<?= e(work_path($w)) ?>" aria-label="Abrir <?= e($w['title']) ?>">

            <!-- Área da capa: se a obra tem imagem, mostra a imagem;
                 se não tem, mostra o texto "SEM CAPA" -->
            <div class="cover <?= e(cover_class($w)) ?>">
                <?php if (!empty($w['image'])): ?>
                    <!-- Se a imagem for um link completo (http/https), usa direto;
                         se for só um caminho interno, completa com o endereço do site -->
                    <img src="<?= e(preg_match('~^https?://~i', $w['image']) ? $w['image'] : url($w['image'])) ?>"
                         alt="<?= e($w['title']) ?>" loading="lazy">
                <?php else: ?>
                    <span>SEM CAPA</span>
                <?php endif; ?>
            </div>

            <!-- Título da obra -->
            <h3><?= e($w['title']) ?></h3>

            <!-- Linha de informação: nome da categoria e, se tiver, o ano separado por "·" -->
            <div class="meta">
                <?= e(category_name($w['category'])) ?><?= ($w['year'] ?? '') ? ' · ' . e($w['year']) : '' ?>
            </div>

            <!-- Sinopse: usa "synopsis" ou, se não tiver, "description".
                 Se nenhum dos dois existir, mostra um texto padrão -->
            <p class="desc"><?= e($w['synopsis'] ?? $w['description'] ?: 'Sinopse ainda não cadastrada.') ?></p>

            <!-- Etiqueta/tag da obra -->
            <span class="tag"><?= e($w['tag']) ?></span>

            <!-- Bloco de segurança: classificação indicativa e aviso de conteúdo sensível -->
            <div class="card-safety">
                <strong>Classificação indicativa: <?= e($w['rating'] ?? 'Não informada') ?></strong>
                <span>⚠ <?= e($w['warning'] ?? 'Conteúdo sensível: horror e imagens perturbadoras.') ?></span>
            </div>

            <!-- Texto que indica que o cartão é clicável -->
            <div class="card-open">Abrir página do analog →</div>
        </a>
    <?php endforeach; ?>
</div>

<!-- Se nenhuma obra passou pelos filtros, mostra uma mensagem avisando -->
<?php if (!$items): ?>
    <div class="empty">Nenhuma obra corresponde aos filtros informados.</div>
<?php endif; ?>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site (com menu, rodapé, etc.)
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML)
require __DIR__ . '/includes/footer.php';
?>