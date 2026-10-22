<?php
$page_title='Reclame Aqui';$active='complaint';require __DIR__.'/includes/header.php';
check_csrf();$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $d=data_read();$type=trim($_POST['type']??'Reclamação');
 $d['contacts'][]=['id'=>count($d['contacts'])+1,'type'=>$type,'message'=>trim($_POST['message']??''),'user'=>user()['id']??null,'status'=>'Aberto'];data_write($d);
 $msg=$type==='Sugestão'?'Sugestão enviada com sucesso.':'Reclamação enviada com sucesso.';
}
ob_start(); ?>
<div class="breadcrumbs">WWAnalog / Reclame Aqui</div>
<div class="hero compact-hero"><div class="eyebrow">CANAL DE ATENDIMENTO</div><h1>Reclame Aqui</h1><p>Use este espaço para enviar uma reclamação ou compartilhar uma sugestão com a equipe da WWAnalog.</p></div>
<?php if($msg): ?><div class="notice"><?=e($msg)?></div><?php endif; ?>
<div class="two-col complaint-grid">
 <section class="formbox"><div class="eyebrow">01 // RECLAMAÇÃO</div><h2>Escreva sua mensagem</h2><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="type" value="Reclamação"><label>Mensagem</label><textarea class="textarea" name="message" rows="9" required placeholder="Descreva sua reclamação..."></textarea><button class="btn">Enviar reclamação</button></form></section>
 <section class="formbox"><div class="eyebrow">02 // SUGESTÃO</div><h2>Faça uma sugestão</h2><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="type" value="Sugestão"><label>Sugestão</label><textarea class="textarea" name="message" rows="9" required placeholder="Escreva sua sugestão..."></textarea><button class="btn">Enviar sugestão</button></form></section>
</div>
<div class="removal-alert">CASO DESEJE A REMOÇÃO DE UM CONTEÚDO, ATUALIZAÇÃO OU CORREÇÃO, ENTRE EM CONTATO COM NOSSA EQUIPE.</div>
<?php $content=ob_get_clean();shell($content,$active);require __DIR__.'/includes/footer.php'; ?>
