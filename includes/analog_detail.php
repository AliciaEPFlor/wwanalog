<?php
/* WWAnalog — Componente reutilizável para apresentar detalhes de uma obra analógica. */
require_once __DIR__ . '/functions.php';
$slug = $analog_slug ?? '';
$w = find_work($slug);
if (!$w) { http_response_code(404); exit('Obra não encontrada.'); }
$page_title = $w['title']; $active = 'catalog';
require __DIR__ . '/header.php';
$img = trim((string)($w['image'] ?? ''));
$episodes = $w['episodes'] ?? [];
ob_start(); ?>
<div class="breadcrumbs"><a href="<?=e(url('catalogo.php'))?>">Catálogo</a> / <?=e($w['title'])?></div>
<section class="analog-detail-hero">
  <div class="analog-detail-copy">
    <div class="meta"><span class="tag"><?=e(category_name($w['category'] ?? ''))?></span><?php if(!empty($w['tag'])):?><span class="tag"><?=e($w['tag'])?></span><?php endif;?></div>
    <h1><?=e($w['title'])?></h1>
    <p><?=e($w['description'] ?? $w['synopsis'] ?? 'Informações ainda não cadastradas.')?></p>
    <a class="btn analog-watch" href="<?=e(url('episodios.php?obra='.(int)$w['id']))?>">Clique aqui para assistir!</a>
  </div>
  <?php if($img !== ''): ?><div class="analog-cover-wrap"><img class="analog-detail-image" src="<?=e(preg_match('~^https?://~i',$img)?$img:url($img))?>" alt="Capa de <?=e($w['title'])?>"></div><?php endif; ?>
</section>
<section class="panel analog-information">
  <h2>Sobre este Analog</h2>
  <?php if(!empty($w['synopsis'])):?><h3>Sinopse</h3><p><?=nl2br(e($w['synopsis']))?></p><?php elseif(!empty($w['description'])):?><p><?=nl2br(e($w['description']))?></p><?php endif;?>
  <?php if(!empty($w['story'])):?><h3>Enredo</h3><p><?=nl2br(e($w['story']))?></p><?php endif;?>
  <?php if(!empty($w['warning'])):?><div class="notice">⚠ <strong>Aviso de conteúdo:</strong> <?=e($w['warning'])?></div><?php endif;?>
  <div class="analog-facts">
    <div><b>Título original</b><span><?=e($w['original_title'] ?? $w['title'])?></span></div>
    <div><b>Criador</b><span><?=e($w['creator'] ?: 'Não informado')?></span></div>
    <div><b>Canal do criador</b><span><?php if(!empty($w['creator_url'])):?><a href="<?=e($w['creator_url'])?>" target="_blank" rel="noopener noreferrer">Abrir canal oficial</a><?php else:?>Não informado<?php endif;?></span></div>
    <div><b>Ano</b><span><?=e($w['year'] ?: 'Não informado')?></span></div>
    <div><b>Situação</b><span><?=e($w['status'] ?: 'Não informada')?></span></div>
    <div><b>Classificação indicativa</b><span><?=e($w['rating'] ?? 'Não informada')?></span></div>
    <div><b>Categoria</b><span><?=e(category_name($w['category'] ?? ''))?></span></div>
    <div><b>Episódios cadastrados</b><span><?=count($episodes)?></span></div>
  </div>
  <?php if(!empty($w['tags']) && is_array($w['tags'])):?><div class="analog-tags"><?php foreach($w['tags'] as $tag):?><span class="tag"><?=e($tag)?></span><?php endforeach;?></div><?php elseif(!empty($w['tag'])):?><div class="analog-tags"><span class="tag"><?=e($w['tag'])?></span></div><?php endif;?>
  <div class="hero-actions"><a class="btn analog-watch" href="<?=e(url('episodios.php?obra='.(int)$w['id']))?>">Clique aqui para assistir!</a><a class="btn ghost" href="<?=e(url('catalogo.php'))?>">Voltar ao catálogo</a></div>
</section>
<?php $content=ob_get_clean(); shell($content,$active); require __DIR__ . '/footer.php'; ?>
