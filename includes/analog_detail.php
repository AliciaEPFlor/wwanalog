<?php
/* WWAnalog — Componente reutilizável para apresentar detalhes de uma obra analógica. */

// Carrega as funções auxiliares do sistema (helpers, validações, etc.).
require_once __DIR__ . '/functions.php';

// ===== RECEBE O "SLUG" DA OBRA =====
// Este componente é reutilizável: quem o inclui deve definir a variável $analog_slug
// antes de chamar este arquivo (ex.: $analog_slug = 'the-walten-files'; require 'analog.php';).
// Se ninguém definiu, fica vazio.
$slug = $analog_slug ?? '';

// Procura a obra pelo slug no "estoque".
$w = find_work($slug);

// Se a obra não existe, mostra erro 404 e para tudo.
if (!$w) { http_response_code(404); exit('Obra não encontrada.'); }

// Define o título da página e marca "catalog" como aba ativa do menu.
$page_title = $w['title'];
$active = 'catalog';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/header.php';

// Pega a imagem de capa e a lista de episódios da obra.
$img = trim((string)($w['image'] ?? ''));
$episodes = $w['episodes'] ?? [];

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho.
ob_start();
?>

<!-- Migalhas de pão: caminho de navegação (Catálogo / Obra) -->
<div class="breadcrumbs">
    <a href="<?=e(url('catalogo.php'))?>">Catálogo</a> / <?=e($w['title'])?>
</div>

<!-- ===== CABEÇALHO DE DESTAQUE (HERO) ===== -->
<section class="analog-detail-hero">

    <!-- Coluna esquerda: texto -->
    <div class="analog-detail-copy">

        <!-- Tags: categoria + tag da obra (se existir) -->
        <div class="meta">
            <span class="tag"><?=e(category_name($w['category'] ?? ''))?></span>
            <?php if(!empty($w['tag'])):?>
                <span class="tag"><?=e($w['tag'])?></span>
            <?php endif;?>
        </div>

        <!-- Título da obra -->
        <h1><?=e($w['title'])?></h1>

        <!-- Descrição curta: usa "description" ou, se não tiver, "synopsis".
             Se nenhum existir, mostra um texto padrão. -->
        <p><?=e($w['description'] ?? $w['synopsis'] ?? 'Informações ainda não cadastradas.')?></p>

        <!-- Botão principal: leva para a lista de episódios -->
        <a class="btn analog-watch" href="<?=e(url('episodios.php?obra='.(int)$w['id']))?>">Clique aqui para assistir!</a>
    </div>

    <!-- Coluna direita: capa (só aparece se houver imagem) -->
    <?php if($img !== ''): ?>
        <div class="analog-cover-wrap">
            <!-- Se a imagem for URL completa (http/https), usa direto; senão, prefixa com url() -->
            <img class="analog-detail-image"
                 src="<?=e(preg_match('~^https?://~i',$img)?$img:url($img))?>"
                 alt="Capa de <?=e($w['title'])?>">
        </div>
    <?php endif; ?>
</section>

<!-- ===== PAINEL DE INFORMAÇÕES DETALHADAS ===== -->
<section class="panel analog-information">
    <h2>Sobre este Analog</h2>

    <!-- Sinopse: se existir, mostra num bloco próprio.
         Senão, se houver descrição, mostra ela. -->
    <?php if(!empty($w['synopsis'])):?>
        <h3>Sinopse</h3>
        <p><?=nl2br(e($w['synopsis']))?></p>
    <?php elseif(!empty($w['description'])):?>
        <p><?=nl2br(e($w['description']))?></p>
    <?php endif;?>

    <!-- Enredo/história: se existir, mostra num bloco próprio. -->
    <?php if(!empty($w['story'])):?>
        <h3>Enredo</h3>
        <p><?=nl2br(e($w['story']))?></p>
    <?php endif;?>

    <!-- Aviso de conteúdo sensível: se existir, mostra num alerta. -->
    <?php if(!empty($w['warning'])):?>
        <div class="notice">⚠ <strong>Aviso de conteúdo:</strong> <?=e($w['warning'])?></div>
    <?php endif;?>

    <!-- ===== FICHA TÉCNICA (pares "rótulo : valor") ===== -->
    <div class="analog-facts">

        <!-- Título original (ou o próprio título se não houver) -->
        <div><b>Título original</b><span><?=e($w['original_title'] ?? $w['title'])?></span></div>

        <!-- Criador (ou "Não informado") -->
        <div><b>Criador</b><span><?=e($w['creator'] ?: 'Não informado')?></span></div>

        <!-- Canal do criador: se houver link, mostra como link clicável;
             senão, mostra "Não informado". -->
        <div>
            <b>Canal do criador</b>
            <span>
                <?php if(!empty($w['creator_url'])):?>
                    <a href="<?=e($w['creator_url'])?>" target="_blank" rel="noopener noreferrer">Abrir canal oficial</a>
                <?php else:?>
                    Não informado
                <?php endif;?>
            </span>
        </div>

        <!-- Ano (ou "Não informado") -->
        <div><b>Ano</b><span><?=e($w['year'] ?: 'Não informado')?></span></div>

        <!-- Situação (ou "Não informada") -->
        <div><b>Situação</b><span><?=e($w['status'] ?: 'Não informada')?></span></div>

        <!-- Classificação indicativa (ou "Não informada") -->
        <div><b>Classificação indicativa</b><span><?=e($w['rating'] ?? 'Não informada')?></span></div>

        <!-- Categoria -->
        <div><b>Categoria</b><span><?=e(category_name($w['category'] ?? ''))?></span></div>

        <!-- Quantidade de episódios cadastrados -->
        <div><b>Episódios cadastrados</b><span><?=count($episodes)?></span></div>
    </div>

    <!-- ===== TAGS ===== -->
    <!-- Se houver uma lista de tags (array), mostra cada uma.
         Senão, se houver uma única tag (string), mostra só ela. -->
    <?php if(!empty($w['tags']) && is_array($w['tags'])):?>
        <div class="analog-tags">
            <?php foreach($w['tags'] as $tag):?>
                <span class="tag"><?=e($tag)?></span>
            <?php endforeach;?>
        </div>
    <?php elseif(!empty($w['tag'])):?>
        <div class="analog-tags">
            <span class="tag"><?=e($w['tag'])?></span>
        </div>
    <?php endif;?>

    <!-- ===== BOTÕES DE AÇÃO ===== -->
    <div class="hero-actions">
        <!-- Botão principal: assistir (vai para a lista de episódios) -->
        <a class="btn analog-watch" href="<?=e(url('episodios.php?obra='.(int)$w['id']))?>">Clique aqui para assistir!</a>

        <!-- Botão secundário: voltar ao catálogo -->
        <a class="btn ghost" href="<?=e(url('catalogo.php'))?>">Voltar ao catálogo</a>
    </div>
</section>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content.
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site.
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__ . '/footer.php';
?>
