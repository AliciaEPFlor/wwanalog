<?php require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Painel do desenvolvedor';
$active = 'admin';
require __DIR__ . '/../../includes/header.php';
$d = data_read();
$filterSearch = trim((string)($_GET['q'] ?? ''));
$filterCategory = trim((string)($_GET['category'] ?? ''));
$filterStatus = trim((string)($_GET['status'] ?? ''));
$filterPeriod = (string)($_GET['period'] ?? '30');
$periodDays = in_array($filterPeriod, ['7','30','90','365'], true) ? (int)$filterPeriod : 30;
$registeredIds = [];
$registeredEmails = [];
foreach ($d['users'] as $u) {
    $uid = (int)($u['id'] ?? 0);
    if ($uid > 0) $registeredIds[$uid] = true;
    $email = mb_strtolower(trim((string)($u['email'] ?? '')));
    if ($email !== '') $registeredEmails[$email] = true;
}
// O painel administrativo deve medir ações de contas cadastradas, não ações do administrador atual nem visitantes anônimos.
$isRegisteredAction = static function ($row) use ($registeredIds, $registeredEmails) {
    $uid = (int)($row['user_id'] ?? 0);
    $email = mb_strtolower(trim((string)($row['email'] ?? '')));
    return ($uid > 0 && isset($registeredIds[$uid])) || ($email !== '' && isset($registeredEmails[$email]));
};
$registeredAudit = array_values(array_filter($d['audit'], $isRegisteredAction));
$registeredComments = array_values(array_filter($d['comments'], $isRegisteredAction));
$published = array_filter($registeredComments, fn($c) => ($c['status'] ?? 'published') === 'published');
$flagged = array_filter($registeredComments, fn($c) => in_array($c['status'] ?? 'published', ['flagged', 'pending', 'removed'], true));
$since = strtotime('-' . $periodDays . ' days');
$periodAudit = array_values(array_filter($registeredAudit, fn($a) => strtotime($a['at'] ?? '1970-01-01') >= $since));
$activeUsers = [];
foreach ($periodAudit as $a) { $uid = (int)($a['user_id'] ?? 0); if ($uid > 0 && isset($registeredIds[$uid])) $activeUsers[$uid] = true; }
$views = array_values(array_filter($d['views'], fn($v) => strtotime($v['at'] ?? '1970-01-01') >= $since && $isRegisteredAction($v)));
$registeredRatings = array_values(array_filter($d['ratings'], $isRegisteredAction));
$counts = [];
foreach ($registeredComments as $c) $counts[$c['work_id'] ?? 0] = ($counts[$c['work_id'] ?? 0] ?? 0) + 1;
// Rankings incluem todas as obras, inclusive as que ainda têm zero visualizações/comentários.
$viewCounts = [];
foreach ($d['works'] as $work) $viewCounts[(int)$work['id']] = 0;
foreach ($views as $view) { $wid=(int)($view['work_id']??0); if(array_key_exists($wid,$viewCounts)) $viewCounts[$wid]++; }
$commentCounts = [];
foreach ($d['works'] as $work) $commentCounts[(int)$work['id']] = 0;
foreach ($registeredComments as $comment) { $wid=(int)($comment['work_id']??0); if(array_key_exists($wid,$commentCounts)) $commentCounts[$wid]++; }
$mostViewed=$viewCounts; arsort($mostViewed); $leastViewed=$viewCounts; asort($leastViewed);
$mostCommented=$commentCounts; arsort($mostCommented); $leastCommented=$commentCounts; asort($leastCommented);
$commentStatus = static fn($c) => strtolower((string)($c['status'] ?? 'published'));
$commentsAnalyzed = count(array_filter($registeredComments, fn($c) => in_array($commentStatus($c), ['published','approved','removed','flagged','pending','rejected','hidden'], true)));
$commentsRemoved = count(array_filter($registeredComments, fn($c) => in_array($commentStatus($c), ['removed','rejected','hidden'], true)));
$commentsInReview = count(array_filter($registeredComments, fn($c) => in_array($commentStatus($c), ['flagged','pending','under_review','em_analise'], true)));
$commentsDetected = count(array_filter($registeredComments, fn($c) => in_array($commentStatus($c), ['flagged','pending','under_review','em_analise','removed','rejected','hidden'], true)));
$infractions = ['Ofensas'=>0,'Discurso de ódio'=>0,'Conteúdo sexual'=>0,'Spam'=>0,'Outros'=>0];
foreach ($registeredComments as $comment) {
    $text = mb_strtolower((string)($comment['body'] ?? $comment['content'] ?? ''));
    $category = (string)($comment['reason'] ?? $comment['violation_type'] ?? $comment['moderation_reason'] ?? '');
    if ($category !== '') {
        $matched=false; foreach(array_keys($infractions) as $label) if(mb_stripos($category,$label)!==false){$infractions[$label]++;$matched=true;break;}
        if(!$matched && in_array($commentStatus($comment), ['flagged','pending','under_review','em_analise','removed','rejected','hidden'], true)) $infractions['Outros']++;
    } elseif (in_array($commentStatus($comment), ['flagged','pending','under_review','em_analise','removed','rejected','hidden'], true)) {
        // Classificação aproximada por palavras-chave apenas para apoiar a triagem; não substitui moderação humana.
        if (preg_match('/\b(idiota|burro|incompetente|otario|lixo)\b/u',$text)) $infractions['Ofensas']++;
        elseif (preg_match('/\b(racista|nazista|odeio|inferior)\b/u',$text)) $infractions['Discurso de ódio']++;
        elseif (preg_match('/\b(sexo|porn|nude|sexual)\b/u',$text)) $infractions['Conteúdo sexual']++;
        elseif (preg_match('/\b(compre|clique aqui|promoção|http|www\.)\b/u',$text)) $infractions['Spam']++;
        else $infractions['Outros']++;
    }
}
arsort($infractions);
$wordFrequency=[];
$stopWords=['para','como','mais','isso','essa','esse','muito','muita','porque','quando','sobre','ainda','estou','obra','episódio','com','uma','que','não','dos','das','por','the','and','you','this','that'];
foreach($registeredComments as $comment){if(in_array($commentStatus($comment),['removed','rejected','hidden'],true))continue;$body=(string)($comment['body']??$comment['content']??'');$words=preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower($body));foreach($words as $word){if(mb_strlen($word)>=4&&!in_array($word,$stopWords,true))$wordFrequency[$word]=($wordFrequency[$word]??0)+1;}}
arsort($wordFrequency);
?><div class="breadcrumbs">Área restrita / Desenvolvedor</div>
<div class="section-head">
    <div>
        <div class="eyebrow">PAINEL RESTRITO</div>
        <h1>Painel do desenvolvedor</h1><small>Métricas calculadas somente a partir dos eventos e registros locais.</small>
    </div><a class="btn" href="new.php">+ Registrar obra</a>
</div>
<div class="grid">
    <div class="card">
        <h3>Ações de usuários cadastrados</h3><strong><?= count($registeredAudit) ?></strong>
    </div>
    <div class="card">
        <h3>Usuários ativos (30 dias)</h3><strong><?= count($activeUsers) ?></strong>
    </div>
    <div class="card">
        <h3>Comentários de usuários cadastrados</h3><strong><?= count($registeredComments) ?></strong>
    </div>
    <div class="card">
        <h3>Comentários sinalizados / pendentes</h3><strong><?= count($flagged) ?></strong>
    </div>
    <div class="card">
        <h3>Visualizações de usuários cadastrados</h3><strong><?= count($views) ?></strong>
    </div>
    <div class="card">
        <h3>Avaliações de usuários cadastrados</h3><strong><?= count($registeredRatings) ?></strong>
    </div>
</div>
<div class="panel" style="margin-top:22px">
    <h2>Filtros dos registros</h2>
    <form method="get" class="filters" style="margin:14px 0">
        <input class="input" type="search" name="q" placeholder="Buscar obra pelo título" value="<?= e($filterSearch) ?>">
        <select class="input" name="category"><option value="">Todas as categorias</option><?php foreach($d['categories'] as $cat): ?><option value="<?=e($cat['slug']??$cat['name']??'')?>" <?= $filterCategory===($cat['slug']??$cat['name']??'')?'selected':'' ?>><?=e($cat['name']??'Categoria')?></option><?php endforeach; ?></select>
        <select class="input" name="status"><option value="">Todas as situações</option><?php foreach(array_unique(array_map(fn($x)=>(string)($x['status']??''),$d['works'])) as $st): if($st==='')continue; ?><option value="<?=e($st)?>" <?=$filterStatus===$st?'selected':''?>><?=e($st)?></option><?php endforeach; ?></select>
        <select class="input" name="period"><option value="7" <?=$periodDays===7?'selected':''?>>Últimos 7 dias</option><option value="30" <?=$periodDays===30?'selected':''?>>Últimos 30 dias</option><option value="90" <?=$periodDays===90?'selected':''?>>Últimos 90 dias</option><option value="365" <?=$periodDays===365?'selected':''?>>Últimos 12 meses</option></select>
        <button class="btn" type="submit">Aplicar filtros</button><a class="btn ghost" href="index.php">Limpar</a>
    </form>
    <p class="small muted">Este painel mostra ações de contas cadastradas (membros), não apenas ações do administrador conectado. As métricas usam o período selecionado; a tabela abaixo usa busca, categoria e situação. Visitantes sem conta não entram nas métricas de atividade dos membros.</p>
</div>
<div class="admin-analytics-grid" style="margin-top:22px">
  <section class="panel analytics-wide">
    <h2>Visão geral de atividade</h2><p class="small muted">Somente ações associadas a contas cadastradas no período selecionado.</p>
    <div class="analytics-three">
      <div class="metric-box"><span>Usuários ativos</span><strong><?=count($activeUsers)?></strong><small>membros com ação registrada</small></div>
      <div class="metric-box"><span>Comentários</span><strong><?=count($registeredComments)?></strong><small>comentários de membros</small></div>
      <div class="metric-box"><span>Denúncias</span><strong><?=count(array_filter($registeredComments,fn($c)=>in_array($commentStatus($c),['flagged','pending','under_review','em_analise'],true)))?></strong><small>comentários sinalizados ou pendentes</small></div>
    </div>
  </section>
  <section class="panel"><h2>Obras mais vistas</h2><div class="analytics-subtitle">Mais vistas</div>
    <?php foreach(array_slice($mostViewed,0,5,true) as $wid=>$num): $rw=find_work($wid); if($rw): ?><div class="kv"><b><?=e($rw['title'])?></b><span><?=$num?></span></div><?php endif; endforeach; ?>
    <div class="analytics-subtitle">Menos vistas</div><?php foreach(array_slice($leastViewed,0,5,true) as $wid=>$num): $rw=find_work($wid); if($rw): ?><div class="kv"><b><?=e($rw['title'])?></b><span><?=$num?></span></div><?php endif; endforeach; ?>
    <p class="small muted">Visualizações registradas de membros no período.</p>
  </section>
  <section class="panel"><h2>Obras com mais comentários</h2><div class="analytics-subtitle">Mais comentários</div>
    <?php foreach(array_slice($mostCommented,0,5,true) as $wid=>$num): $rw=find_work($wid); if($rw): ?><div class="kv"><b><?=e($rw['title'])?></b><span><?=$num?></span></div><?php endif; endforeach; ?>
    <div class="analytics-subtitle">Menos comentários</div><?php foreach(array_slice($leastCommented,0,5,true) as $wid=>$num): $rw=find_work($wid); if($rw): ?><div class="kv"><b><?=e($rw['title'])?></b><span><?=$num?></span></div><?php endif; endforeach; ?>
  </section>
  <section class="panel"><h2>Comentários analisados</h2><div class="metric-box"><span>Total analisado</span><strong><?=$commentsAnalyzed?></strong><small>registros de comentários de membros</small></div>
    <div class="analytics-subtitle">Palavras mais usadas</div><?php $shownWords=0; foreach($wordFrequency as $word=>$qty): ?><div class="kv"><b><?=e($word)?></b><span><?=$qty?></span></div><?php if(++$shownWords>=10)break; endforeach; if(!$shownWords): ?><p class="empty">Ainda não há comentários suficientes para gerar este resumo.</p><?php endif; ?>
  </section>
  <section class="panel"><h2>Comentários inadequados</h2>
    <div class="analytics-three analytics-three-small"><div class="metric-box"><span>Total detectados</span><strong><?=$commentsDetected?></strong></div><div class="metric-box"><span>Removidos</span><strong><?=$commentsRemoved?></strong></div><div class="metric-box"><span>Em análise</span><strong><?=$commentsInReview?></strong></div></div>
    <div class="analytics-subtitle">Principais tipos de infração</div><?php foreach($infractions as $label=>$qty): ?><div class="kv"><b><?=e($label)?></b><span><?=$qty?></span></div><?php endforeach; ?>
    <p class="small muted">A classificação por palavras-chave é aproximada e serve apenas como apoio; confirme cada caso manualmente. Os totais refletem os status e motivos realmente armazenados.</p>
  </section>
</div>
<div class="panel" style="margin-top:22px">
    <h2>Obras — visualizar e editar</h2>
    <div class="table">
        <table class="table">
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Situação</th>
                <th>Visualizações</th>
                <th>Comentários</th>
                <th>Ação</th>
            </tr><?php $filteredWorks=array_values(array_filter($d['works'],function($w) use($filterSearch,$filterCategory,$filterStatus){$cat=(string)($w['category']??'');$title=(string)($w['title']??'');$status=(string)($w['status']??'');return ($filterSearch===''||mb_stripos($title,$filterSearch)!==false)&&($filterCategory===''||$cat===$filterCategory||category_name($cat)===$filterCategory)&&($filterStatus===''||$status===$filterStatus);})); foreach ($filteredWorks as $w): $vc = count(array_filter($d['views'], fn($v) => (int)($v['work_id'] ?? 0) === (int)$w['id'])); ?><tr>
                    <td><?= e($w['title']) ?></td>
                    <td><?= e(category_name($w['category'])) ?></td>
                    <td><?= e($w['status'] ?? 'Não cadastrada') ?></td>
                    <td><?= $vc ?></td>
                    <td><?= $counts[$w['id']] ?? 0 ?></td>
                    <td><a class="btn ghost" href="edit.php?id=<?= (int)$w['id'] ?>">Editar</a> <a class="btn ghost" href="<?= e(url('obra.php?id=' . (int)$w['id'])) ?>">Visualizar</a></td>
                </tr><?php endforeach; if(!$filteredWorks): ?><tr><td colspan="6">Nenhuma obra corresponde aos filtros.</td></tr><?php endif; ?>
        </table>
    </div>
</div>
<div class="two-col" style="margin-top:22px">
    <section class="panel">
        <h2>Ações recentes</h2><?php $recent = array_slice(array_reverse($periodAudit), 0, 12);
                                if (!$recent): ?><p class="empty">Nenhuma ação registrada ainda.</p><?php else: foreach ($recent as $a): ?><article class="comment">
                    <div class="eyebrow"><?= e($a['at'] ?? '') ?> · <?= e($a['action'] ?? 'ação') ?></div>
                    <p><?= e($a['email'] ?? '') ?> — <?= e($a['details'] ?? '') ?></p>
                </article><?php endforeach;
                                                                                                                                                        endif; ?>
    </section>
    <section class="panel">
        <h2>Comentários mais comentados por obra</h2><?php arsort($counts);
                                                        foreach (array_slice($counts, 0, 10, true) as $wid => $num): $w = find_work($wid);
                                                            if ($w): ?><div class="kv"><b><?= e($w['title']) ?></b><span><?= $num ?></span></div><?php endif;
                                                                                                                                                                                                                            endforeach; ?><p class="small muted">A distribuição é calculada com os comentários armazenados. Não há gráfico temporal nesta versão sem biblioteca de gráficos.</p>
    </section>
</div>

<div class="two-col" style="margin-top:22px">
 <section class="panel"><h2>Obras mais e menos visualizadas (30 dias)</h2>
 <?php $vcounts=[]; foreach($views as $v){$id=(int)($v['work_id']??0);$vcounts[$id]=($vcounts[$id]??0)+1;} arsort($vcounts); $ranked=array_slice($vcounts,0,5,true); foreach($ranked as $id=>$n): $vw=find_work($id); if($vw): ?><div class="kv"><b><?=e($vw['title'])?></b><span><?=$n?> visualizações</span></div><?php endif; endforeach; if(!$ranked): ?><p class="empty">Ainda não há visualizações suficientes no período.</p><?php endif; ?>
 <?php asort($vcounts); ?><h3>Menos visualizadas</h3><?php foreach(array_slice($vcounts,0,5,true) as $id=>$n): $vw=find_work($id); if($vw): ?><div class="kv"><b><?=e($vw['title'])?></b><span><?=$n?></span></div><?php endif; endforeach; ?>
 </section>
 <section class="panel"><h2>Comentários por obra</h2>
 <?php $sortedComments=$counts; arsort($sortedComments); foreach(array_slice($sortedComments,0,10,true) as $wid=>$num): $cw=find_work($wid); if($cw): ?><div class="kv"><b><?=e($cw['title'])?></b><span><?=$num?></span></div><?php endif; endforeach; ?>
 <p class="small muted">Distribuição baseada nos comentários registrados. Obras sem comentários não aparecem neste ranking.</p></section>
</div>
<div class="two-col" style="margin-top:22px">
 <section class="panel"><h2>Reclamações e sugestões</h2>
 <?php $requests=array_slice(array_reverse($d['contacts']),0,15); if(!$requests): ?><p class="empty">Nenhuma reclamação ou sugestão registrada.</p><?php else: foreach($requests as $r): ?><article class="comment"><div class="eyebrow"><?=e($r['type']??'Contato')?> · <?=e($r['status']??'Aberto')?></div><p><?=e($r['message']??'')?></p></article><?php endforeach; endif; ?>
 </section>
 <section class="panel"><h2>Palavras frequentes nos comentários</h2>
 <?php $freq=[]; foreach($registeredComments as $cm){if(($cm['status']??'published')==='removed')continue; $words=preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower($cm['body']??'')); foreach($words as $word){if(mb_strlen($word)<4)continue;$freq[$word]=($freq[$word]??0)+1;}} arsort($freq); $stop=['para','como','mais','isso','essa','esse','muito','muita','porque','quando','sobre','ainda','estou','obra','episódio','com','uma','que','não','dos','das','por']; $shown=0; foreach($freq as $word=>$qty){if(in_array($word,$stop,true))continue; ?><div class="kv"><b><?=e($word)?></b><span><?=$qty?></span></div><?php if(++$shown>=10)break;} if(!$shown): ?><p class="empty">Ainda não há dados suficientes para uma análise de termos.</p><?php endif; ?>
 <p class="small muted">Resumo agregado; não é uma avaliação automática de infração.</p></section>
</div>
<div class="hero-actions"><a class="btn ghost" href="comments.php">Moderar comentários</a><a class="btn ghost" href="episodes.php">Gerenciar episódios</a></div><?php require __DIR__ . '/../../includes/footer.php'; ?>