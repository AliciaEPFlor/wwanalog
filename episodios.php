<?php
/* WWAnalog — Lista pública de episódios registrados. */

// Carrega as funções auxiliares do sistema (helpers, validações, etc.)
require_once __DIR__ . '/includes/functions.php';

// Procura a obra pelo parâmetro "obra" da URL. Se não existir, retorna vazio.
$w = find_work($_GET['obra'] ?? '');

// Se a obra não existe, mostra erro 404 e para tudo.
// É como: "essa página não existe".
if (!$w) {
    http_response_code(404);
    exit('Obra não encontrada.');
}

// Diz ao menu qual aba deve ficar destacada (a aba "Catálogo")
$active = 'catalog';

// Define o título da página com o nome da obra
$page_title = 'Episódios — ' . $w['title'];

// Carrega o topo do site (logo, menu, etc.)
require __DIR__ . '/includes/header.php';

// Lê os dados do "estoque" (arquivo de dados)
$d = data_read();

// Registra no histórico que alguém abriu a lista de episódios desta obra.
// Guarda: tipo do evento, ID da obra, ID do usuário (0 se não estiver logado) e data/hora.
// Isso serve para estatísticas de acesso.
$d['views'][] = [
    'type'    => 'episode_list',
    'work_id' => (int) $w['id'],
    'user_id' => (int) (user()['id'] ?? 0),
    'at'      => date('c')
];

// Salva o registro no "estoque"
data_write($d);

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho
ob_start();
?>

<!-- Migalhas de pão: mostra o caminho (Catálogo / Obra / Episódios) -->
<div class="breadcrumbs">
    <a href="<?= e(url('catalogo.php')) ?>">Catálogo</a> /
    <a href="<?= e(url('obra.php?id=' . (int) $w['id'])) ?>"><?= e($w['title']) ?></a> / Episódios
</div>

<!-- Cabeçalho da seção: título da obra e quantos episódios existem -->
<div class="section-head">
    <div>
        <div class="eyebrow">ARQUIVO DE EPISÓDIOS</div>
        <h1><?= e($w['title']) ?></h1>
        <!-- count() conta quantos episódios existem. Se não tiver nenhum, mostra 0 -->
        <small><?= count($w['episodes'] ?? []) ?> episódio(s) cadastrado(s)</small>
    </div>
</div>

<!-- Painel que lista os episódios -->
<div class="panel">

    <!-- Se a obra TEM episódios, mostra a lista -->
    <?php if (!empty($w['episodes'])):
        foreach ($w['episodes'] as $i => $ep): ?>

        <?php
        // Monta o link para a página do episódio (usando o índice $i)
        $episodeHref = url('episodio.php?obra=' . (int) $w['id'] . '&ep=' . $i);

        // Verifica se existe um vídeo LOCAL cadastrado e se o arquivo realmente existe no servidor.
        // $hasLocal será true ou false.
        $hasLocal = !empty($ep['local_video']) && is_file(__DIR__ . '/' . ltrim($ep['local_video'], '/'));
        ?>

        <!-- Cada episódio vira um link clicável -->
        <a class="episode" href="<?= e($episodeHref) ?>">

            <!-- Número do episódio formatado com 2 dígitos (01, 02, ...).
                 Usa o campo "number" se existir; senão usa o índice + 1 -->
            <div class="episode-no"><?= str_pad((string) ($ep['number'] ?? ($i + 1)), 2, '0', STR_PAD_LEFT) ?></div>

            <div>
                <!-- Título do episódio. Se não tiver, mostra "Episódio N" -->
                <h3><?= e($ep['title'] ?? 'Episódio ' . ($i + 1)) ?></h3>
                <p>Episódio <?= str_pad((string) ($ep['number'] ?? ($i + 1)), 2, '0', STR_PAD_LEFT) ?></p>
            </div>

            <!-- Etiqueta que indica o status do episódio:
                 - Se tem vídeo local: "Assistir no WWAnalog"
                 - Se tem link externo: "Abrir episódio"
                 - Se não tem nada: "Vídeo não enviado" -->
            <span class="tag"><?= $hasLocal ? 'Assistir no WWAnalog' : (!empty($ep['url']) ? 'Abrir episódio' : 'Vídeo não enviado') ?></span>
        </a>

        <?php endforeach; ?>

    <!-- Se NÃO tem episódios, mostra mensagem avisando -->
    <?php else: ?>
        <div class="empty">Nenhum episódio foi cadastrado. A equipe poderá adicioná-los pelo painel do desenvolvedor.</div>
    <?php endif; ?>
</div>

<!-- Botões de atalho para outras telas -->
<div class="hero-actions">
    <a class="btn ghost" href="<?= e(url('obra.php?id=' . (int) $w['id'])) ?>">Voltar aos detalhes</a>
    <a class="btn ghost" href="<?= e(url('catalogo.php')) ?>">Catálogo</a>
</div>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML)
require __DIR__ . '/includes/footer.php';
?>