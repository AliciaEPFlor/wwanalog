<?php
require_once __DIR__.'/config.php';
function data_read(){
 $d=require __DIR__.'/../database/data.php';foreach(['users','works','categories','comments','ratings','lists','audit','contacts','forum','chat_logs','views'] as $k)if(!isset($d[$k])||!is_array($d[$k]))$d[$k]=[];
 return $d;
}
function data_write($d){$file=__DIR__.'/../database/data.php';$tmp=$file.'.tmp';$bytes=file_put_contents($tmp,"<?php\nreturn ".var_export($d,true).";\n",LOCK_EX);if($bytes===false)throw new RuntimeException('Não foi possível salvar os dados.');if(!rename($tmp,$file)){@unlink($tmp);throw new RuntimeException('Não foi possível atualizar o banco local.');}}
function audit_event($action,$details=''){ $d=data_read();$d['audit'][]=['at'=>date('c'),'user_id'=>(int)(user()['id']??0),'email'=>user()['email']??'visitante','action'=>$action,'details'=>$details];if(count($d['audit'])>2000)$d['audit']=array_slice($d['audit'],-2000);data_write($d); }
function work_comments($workId,$episodeIndex=null){$d=data_read();return array_values(array_filter($d['comments'],function($c)use($workId,$episodeIndex){return (int)($c['work_id']??0)===(int)$workId && (($episodeIndex===null && !isset($c['episode_index'])) || ($episodeIndex!==null && isset($c['episode_index']) && (int)$c['episode_index']===(int)$episodeIndex)) && (($c['status']??'published')==='published');}));}
function work_rating_average($workId){$d=data_read();$a=array_values(array_filter($d['ratings'],fn($r)=>(int)($r['work_id']??0)===(int)$workId));if(!$a)return ['average'=>0,'count'=>0];return ['average'=>round(array_sum(array_column($a,'stars'))/count($a),1),'count'=>count($a)];}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function app_base_url(){
 // Aceita URL completa (produção), caminho-base (subpasta) ou autodetecção local.
 $configured = trim((string) (getenv('WWANALOG_BASE_URL') ?: ''));
 if ($configured !== '' && preg_match('~^https?://~i', $configured)) {
  return rtrim($configured, '/');
 }
 if ($configured !== '') {
  return app_origin() . '/' . trim($configured, '/');
 }
 $appRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
 $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? (realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']) : '';
 $basePath = '';
 if ($docRoot !== '' && str_starts_with(str_replace('\\', '/', $appRoot), rtrim(str_replace('\\', '/', $docRoot), '/') . '/')) {
  $basePath = '/' . trim(substr(str_replace('\\', '/', $appRoot), strlen(rtrim(str_replace('\\', '/', $docRoot), '/'))), '/');
 }
 return app_origin() . rtrim($basePath, '/');
}
function app_origin(){
 $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off');
 $scheme = $https ? 'https' : 'http';
 $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
 // Evita que um Host malformado injete conteúdo em URLs geradas.
 if (!preg_match('/^[a-zA-Z0-9.\-\[\]:]+$/', $host)) $host = 'localhost';
 return $scheme . '://' . $host;
}
function url($p=''){
 $p = ltrim((string) $p, '/');
 $base = app_base_url();
 return $base . ($p !== '' ? '/' . $p : '/');
}
function user(){return $_SESSION['user']??null;} function logged(){return !!user();} function admin(){return (user()['role']??'')==='Administrador';}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));return $_SESSION['csrf'];}
function check_csrf(){if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);exit('CSRF inválido.');}}
function require_login(){if(!logged()){header('Location: '.url('login/login.php'));exit;}}
function require_admin(){if(!admin()){http_response_code(403);exit('Acesso negado.');}}
function category_name($slug){foreach(data_read()['categories'] as $c)if($c['slug']===$slug)return $c['name'];return $slug;}
function find_work($id){foreach(data_read()['works'] as $w)if((string)$w['id']===(string)$id||$w['slug']===$id)return $w;return null;}
function cover_class($w){$c=$w['category']??'';if($c==='bcasthijacks')return'blue';if($c==='cttpsas'||$c==='args')return'yellow';if($c==='foundfootage'||$c==='lostmedia')return'red';return'';}
function work_path($w){$pages=array('themonumentmythos'=>'/analogs/foundfootage/themmythos/themmythos.php','winterof83'=>'/analogs/foundfootage/winterof83/winterof83.php','backrooms'=>'/analogs/foundfootage/backrooms/backrooms.php','vcrwillie'=>'/analogs/foundfootage/vcrwillie/vcrwillie.php','notroad'=>'/analogs/foundfootage/notroad/notroad.php','mhornets'=>'/analogs/foundfootage/mhornets/mhornets.php','eventide'=>'/analogs/bcasthijacks/emc/emc.php','channel7'=>'/analogs/bcasthijacks/channel7/channel7.php','bluechannel'=>'/analogs/bcasthijacks/bluechannel/bluechannel.php','duskbcast'=>'/analogs/bcasthijacks/duskbcast/duskbcast.php','geminihoen'=>'/analogs/bcasthijacks/geminihoen/geminihoen.php','local58'=>'/analogs/bcasthijacks/local58/local58.php','themanact'=>'/analogs/bcasthijacks/themanact/themanact.php','wlandsnapa'=>'/analogs/cttpsas/wlandsnapa/wlandsnapa.php','chss'=>'/analogs/cttpsas/chss/chss.php','wseducation'=>'/analogs/cttpsas/wsedcation/wsedcation.php','lacys'=>'/analogs/lostmedia/lacys/lacys.php','cchippas'=>'/analogs/lostmedia/cchippas/cchippas.php','petscop'=>'/analogs/lostmedia/petscop/petscop.php','waltenfiles'=>'/analogs/lostmedia/waltenfiles/waltenfiles.php','liminalland'=>'/analogs/args/liminalland/liminalland.php','mfpnpark'=>'/analogs/args/mfpnpark/mfpnpark.php','thecomplex'=>'/analogs/liminalsp/thecomplex/thecomplex.php','theoldview'=>'/analogs/liminalsp/theoldview/theoldview.php','krainagrzy'=>'/analogs/mncorro/krainagry/krainagry.php','harmonyhorror'=>'/analogs/mncorro/harmonyhorror/harmonyhorror.php','ssbar'=>'/analogs/mncorro/ssbar/ssbar.php','vitacarnis'=>'/analogs/ancorro/vitacarnis/vitacarnis.php','roforest'=>'/analogs/ancorro/roforest/roforest.php','sm64c'=>'/analogs/imicorro/sm64c/sm64c.php','tmk'=>'/analogs/imicorro/tmk/tmk.php','ndm0use'=>'/analogs/imicorro/ndm0use/ndm0use.php','magnitoshakhtinskaia'=>'/analogs/bcasthijacks/magnitoshakhtinskaia/magnitoshakhtinskaia.php');return url($pages[$w['slug']]??('obra.php?id='.rawurlencode($w['id'])));}
function icon($name){
 $icons=[
  'home'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5M9 21v-6h6v6"/></svg>',
  'catalog'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>',
  'contact'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
  'complaint'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 21 20H3L12 3Z"/><path d="M12 9v5M12 17h.01"/></svg>',
  'info'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 10v6M12 7h.01"/></svg>',
  'about'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V4h12v17M16 8h4v13M8 8h4M8 12h4M8 16h4"/></svg>',
  'ai'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/><circle cx="12" cy="12" r="4"/></svg>',
  'admin'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.2 8.5-8 10-4.8-1.5-8-5-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/></svg>'
 ];
 return $icons[$name]??'';
}
function layout_start($title='',$active=''){
 $name=$title?e($title).' — WWAnalog':'WWAnalog — Wiki Watch Analog';
 echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="WWAnalog — Wiki Watch Analog"><title>'.$name.'</title><link rel="stylesheet" href="'.e(url('style/style.css')).'"></head><body><header class="site-header"><div class="header-inner"><a class="brand" href="'.e(url('index.php')).'"><img class="brand-logo" src="'.e(url('assets/logo.jpg')).'" alt="Logo WWAnalog"><span class="brand-name">WW<span>Analog</span></span></a><div class="header-status">ARCHIVE // ONLINE</div><div class="header-account">'.(logged()?'<a class="top-login" href="'.e(url('login/perfil.php')).'">'.icon('info').'<span>Perfil</span></a>':'<a class="top-login" href="'.e(url('login/login.php')).'">'.icon('info').'<span>Login</span></a>').'</div></div></header><div class="page">';
}
function layout_end(){echo '</div><footer class="footer"><div class="footer-inner"><div><strong>WWAnalog</strong><br>Wiki Watch Analog — catálogo independente de obras de Analog Horror.</div><div><strong>Projeto</strong><br>Catalogação, organização e referência.</div><div><strong>Contato</strong><br><a href="'.e(url('contatos/index.php')).'">Fale com a equipe</a></div></div></footer></body></html>';}
function shell($content,$active=''){
 $active=$active ?: ($GLOBALS['active']??'');
 $links=[
  ['home','Início','index.php','home'],
  ['catalog','Catálogo','catalogo.php','catalog'],
  ['ai','IA','ia.php','ai'],
  ['contact','Contatos','contatos/index.php','contact'],
  ['complaint','Reclame Aqui','reclamacoes.php','complaint'],
  ['info','O que é','oquee.php','info'],
  ['about','Sobre','sobrewwanalog.php','about']
 ];
 if(admin()) $links[]=['admin','Admin','people/admin/index.php','admin'];
 $rail='<aside class="access-rail" aria-label="Trilhas de acesso"><div class="rail-label">TRILHAS</div><nav class="rail-nav">';
 foreach($links as $l){$rail.='<a class="rail-link '.($active===$l[3]?'active':''). '" href="'.e(url($l[2])).'" title="'.e($l[1]).'" aria-label="'.e($l[1]).'">'.icon($l[0]).'<span class="sr-only">'.e($l[1]).'</span></a>';}
 $rail.='</nav></aside>';
 echo '<div class="layout">'.$rail.'<section class="content">'.$content.'</section></div>';
}
?>
