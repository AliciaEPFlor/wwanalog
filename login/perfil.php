<?php
require_once __DIR__.'/../includes/functions.php';
require_login();
$page_title='Perfil';$active='';require __DIR__.'/../includes/header.php';
check_csrf();$d=data_read();$current=user();$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $uid=(int)$current['id'];
 foreach($d['users'] as &$u){
  if((int)$u['id']===$uid){
   $u['name']=trim($_POST['name']??$u['name']);
   $u['nickname']=trim($_POST['nickname']??$u['nickname']);
   $u['bio']=trim($_POST['bio']??($u['bio']??''));
   if(isset($_FILES['avatar']) && $_FILES['avatar']['error']===UPLOAD_ERR_OK){
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['avatar']['tmp_name']);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(isset($allowed[$mime]) && $_FILES['avatar']['size']<=2*1024*1024){
      $dir=__DIR__.'/../assets/uploads';if(!is_dir($dir))mkdir($dir,0755,true);
      $file='avatar_'.$uid.'.'.$allowed[$mime];move_uploaded_file($_FILES['avatar']['tmp_name'],$dir.'/'.$file);$u['avatar']='assets/uploads/'.$file;
    } else $err='A foto deve ser JPG, PNG ou WEBP e ter no máximo 2 MB.';
   }
   $current=$u;$_SESSION['user']=$u;break;
  }
 }
 data_write($d);$d=data_read();
 if(!$err)$msg='Perfil salvo com sucesso.';
}
$current=user();
$seen=[];$watching=[];
foreach($d['lists'] as $l){if((int)($l['user']??0)!==(int)$current['id'])continue;$w=find_work($l['work']??0);if(!$w)continue;if(($l['status']??'')==='Assistido')$seen[]=$w;if(($l['status']??'')==='Assistindo')$watching[]=$w;}
ob_start(); ?>
<div class="breadcrumbs">WWAnalog / Perfil</div>
<div class="profile-layout">
<section class="profile-card card">
 <div class="profile-avatar"><?php if(!empty($current['avatar'])):?><img src="<?=e(preg_match('~^https?://~i',$current['avatar'])?$current['avatar']:url($current['avatar']))?>" alt="Foto de perfil"><?php else:?><span><?=e(strtoupper(substr($current['nickname']?:$current['name'],0,1)))?></span><?php endif;?></div>
 <div class="profile-identity"><div class="eyebrow">CONTA // <?=e($current['role'])?></div><h1><?=e($current['nickname']?:$current['name'])?></h1><p class="muted"><?=e($current['email'])?></p></div>
</section>
<section class="formbox formbox-wide">
 <div class="eyebrow">EDITAR PERFIL</div><h2>Seu espaço</h2>
 <?php if($msg):?><div class="notice success"><?=e($msg)?></div><?php endif;?><?php if($err):?><div class="notice"><?=e($err)?></div><?php endif;?>
 <form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=csrf()?>">
  <div class="form-grid"><div class="field"><label>Nome</label><input class="input" name="name" value="<?=e($current['name']??'')?>"></div><div class="field"><label>Apelido</label><input class="input" name="nickname" value="<?=e($current['nickname']??'')?>"></div><div class="field full"><label>Foto de perfil</label><input class="input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></div><div class="field full"><label>Descrição / sobre você</label><textarea class="textarea" name="bio" rows="7" placeholder="Escreva algo sobre você..."><?=e($current['bio']??'')?></textarea></div></div>
  <button class="btn">Salvar perfil</button>
 </form>
</section>
</div>
<div class="profile-lists">
 <section class="panel"><div class="eyebrow">ARQUIVO PESSOAL // 01</div><h2>O que eu já vi</h2><?php if($seen):?><div class="mini-list"><?php foreach($seen as $w):?><a href="<?=work_path($w)?>"><?=e($w['title'])?><span>Assistido</span></a><?php endforeach;?></div><?php else:?><div class="empty">Você ainda não marcou nenhuma obra como assistida.</div><?php endif;?></section>
 <section class="panel"><div class="eyebrow">ARQUIVO PESSOAL // 02</div><h2>O que estou vendo</h2><?php if($watching):?><div class="mini-list"><?php foreach($watching as $w):?><a href="<?=work_path($w)?>"><?=e($w['title'])?><span>Assistindo</span></a><?php endforeach;?></div><?php else:?><div class="empty">Você ainda não marcou nenhuma obra como em andamento.</div><?php endif;?></section>
</div>
<section class="panel" style="margin-top:22px"><div class="eyebrow">ARQUIVO PESSOAL // 03</div><h2>Minha lista</h2><?php $saved=[];foreach($d['lists'] as $l){if((int)($l['user']??0)===(int)$current['id']&&($l['status']??'')==='Adicionado à lista'){$x=find_work($l['work']??0);if($x)$saved[]=$x;}}if($saved):?><div class="mini-list"><?php foreach($saved as $x):?><a href="<?=e(work_path($x))?>"><?=e($x['title'])?><span>Adicionado à lista</span></a><?php endforeach;?></div><?php else:?><div class="empty">Sua lista ainda está vazia.</div><?php endif;?></section>
<div class="profile-footer-actions"><a class="btn ghost" href="<?=e(url('catalogo.php'))?>">Voltar ao catálogo</a><a class="btn danger" href="<?=e(url('login/logout.php'))?>">Sair</a></div>
<?php $content=ob_get_clean();shell($content,$active);require __DIR__.'/../includes/footer.php'; ?>
