<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Perfil';
$active = '';
require __DIR__ . '/../includes/header.php';
check_csrf();
$d = data_read();
$current = user();
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uid = (int)$current['id'];
  foreach ($d['users'] as &$u) {
    if ((int)$u['id'] === $uid) {
      $u['name'] = trim($_POST['name'] ?? $u['name']);
      $u['nickname'] = trim($_POST['nickname'] ?? $u['nickname']);
      $u['bio'] = trim($_POST['bio'] ?? ($u['bio'] ?? ''));
      if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['avatar']['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (isset($allowed[$mime]) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
          $dir = __DIR__ . '/../assets/uploads';
          if (!is_dir($dir)) mkdir($dir, 0755, true);
          $file = 'avatar_' . $uid . '.' . $allowed[$mime];
          move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . '/' . $file);
          $u['avatar'] = 'assets/uploads/' . $file;
        } else $err = 'A foto deve ser JPG, PNG ou WEBP e ter no máximo 2 MB.';
      }
      $current = $u;
      $_SESSION['user'] = $u;
      break;
    }
  }
  data_write($d);
  $d = data_read();
  if (!$err) $msg = 'Perfil salvo com sucesso.';
}
$current = user();
// Painel pessoal: os indicadores são recalculados a partir dos registros atuais
// do armazenamento usado por esta versão (database/data.php) a cada acesso.
$uid = (int)($current['id'] ?? 0);
$userLists = array_values(array_filter($d['lists'], fn($l) => (int)($l['user'] ?? 0) === $uid));
$seen = [];
$watching = [];
foreach ($d['lists'] as $l) {
  if ((int)($l['user'] ?? 0) !== (int)$current['id']) continue;
  $w = find_work($l['work'] ?? 0);
  if (!$w) continue;
  if (($l['status'] ?? '') === 'Assistido') $seen[] = $w;
  if (($l['status'] ?? '') === 'Assistindo') $watching[] = $w;
}
$saved = [];
foreach ($userLists as $l) {
  if (($l['status'] ?? '') === 'Adicionado à lista') {
    $x = find_work($l['work'] ?? 0);
    if ($x) $saved[] = $x;
  }
}
$userRatings = array_values(array_filter($d['ratings'], fn($r) => (int)($r['user_id'] ?? 0) === $uid));
$userComments = array_values(array_filter($d['comments'], fn($c) => (int)($c['user_id'] ?? 0) === $uid));
$userAudit = array_values(array_filter($d['audit'], fn($a) => (int)($a['user_id'] ?? 0) === $uid));
$userAuditTotal = count($userAudit);
$userAudit = array_slice(array_reverse($userAudit), 0, 8);
$averageStars = $userRatings ? round(array_sum(array_map(fn($r) => (int)($r['stars'] ?? 0), $userRatings) / count($userRatings)), 1) : 0;
ob_start(); ?>
<div class="breadcrumbs">WWAnalog / Perfil</div>
<div class="profile-layout">
  <section class="profile-card card">
    <div class="profile-avatar"><?php if (!empty($current['avatar'])): ?><img src="<?= e(preg_match('~^https?://~i', $current['avatar']) ? $current['avatar'] : url($current['avatar'])) ?>" alt="Foto de perfil"><?php else: ?><span><?= e(strtoupper(substr($current['nickname'] ?: $current['name'], 0, 1))) ?></span><?php endif; ?></div>
    <div class="profile-identity">
      <div class="eyebrow">CONTA // <?= e($current['role']) ?></div>
      <h1><?= e($current['nickname'] ?: $current['name']) ?></h1>
      <p class="muted"><?= e($current['email']) ?></p>
    </div>
  </section>
  <section class="formbox formbox-wide">
    <div class="eyebrow">EDITAR PERFIL</div>
    <h2>Seu espaço</h2>
    <?php if ($msg): ?><div class="notice success"><?= e($msg) ?></div><?php endif; ?><?php if ($err): ?><div class="notice"><?= e($err) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" id="profile-form"><input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="form-grid">
        <div class="field"><label>Nome</label><input class="input" name="name" value="<?= e($current['name'] ?? '') ?>"></div>
        <div class="field"><label>Apelido</label><input class="input" name="nickname" value="<?= e($current['nickname'] ?? '') ?>"></div>
        <div class="field full"><label>Foto de perfil</label><input class="input" type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/webp"><p class="small muted">Pré-visualização:</p><div id="avatar-preview" class="profile-avatar" style="margin:12px 0"></div></div>
        <div class="field full"><label>Descrição / sobre você</label><textarea class="textarea" name="bio" rows="7" placeholder="Escreva algo sobre você..."><?= e($current['bio'] ?? '') ?></textarea></div>
      </div>
      <button class="btn">Salvar perfil</button>
    </form>
  </section>
</div>
<style>
.user-dashboard{margin:22px 0}.user-dashboard-head{display:flex;align-items:flex-end;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px}.user-dashboard-head h2{margin:4px 0}.user-metric-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.user-metric{display:block;text-decoration:none;border:1px solid var(--line,#333);background:linear-gradient(145deg,rgba(120,20,28,.18),rgba(20,20,20,.7));padding:18px;border-radius:4px;transition:border-color .2s,transform .2s}.user-metric:hover{border-color:#b62a35;transform:translateY(-2px)}.user-metric .metric-label{display:block;color:var(--muted,#aaa);font-size:.82rem}.user-metric strong{display:block;font-size:1.8rem;margin:8px 0}.user-metric .metric-foot{font-size:.78rem;color:#b7b7b7}.user-dashboard-columns{display:grid;grid-template-columns:1.2fr 1fr;gap:18px;margin-top:18px}.user-dashboard-columns .panel{min-width:0}.user-bars{display:grid;gap:12px}.user-bar-row{display:grid;grid-template-columns:minmax(80px,1fr) 2fr 34px;gap:10px;align-items:center;font-size:.84rem}.user-bar-track{height:7px;background:#252525;border-radius:99px;overflow:hidden}.user-bar-fill{height:100%;background:#a8212d;display:block}.user-activity{padding:10px 0;border-bottom:1px solid #303030}.user-activity:last-child{border-bottom:0}.user-activity .eyebrow{font-size:.72rem}.user-shortcuts{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}@media(max-width:900px){.user-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.user-dashboard-columns{grid-template-columns:1fr}}@media(max-width:480px){.user-metric-grid{grid-template-columns:1fr}.user-bar-row{grid-template-columns:90px 1fr 28px}}
</style>
<section class="user-dashboard">
  <div class="user-dashboard-head"><div><div class="eyebrow">CENTRAL DO MEMBRO</div><h2>Visão geral da sua conta</h2><p class="small muted">Resumo da sua conta: avaliações, comentários e atividades registradas. As métricas administrativas ficam no painel de administração.</p></div><span class="small muted">Atualizado ao carregar esta página</span></div>
  <div class="user-metric-grid">
    <a class="user-metric" href="#assistidos"><span class="metric-label">Obras assistidas</span><strong><?= count($seen) ?></strong><span class="metric-foot">Seu histórico pessoal →</span></a>
    <a class="user-metric" href="#atividade-avaliacoes"><span class="metric-label">Avaliações feitas</span><strong><?= count($userRatings) ?></strong><span class="metric-foot">Média das suas notas: <?= e(number_format($averageStars, 1, ',', '.')) ?>/5 →</span></a>
    <a class="user-metric" href="#meus-comentarios"><span class="metric-label">Comentários publicados</span><strong><?= count(array_filter($userComments, fn($c) => ($c['status'] ?? 'published') === 'published')) ?></strong><span class="metric-foot">Seus comentários visíveis →</span></a>
    <a class="user-metric" href="#meus-comentarios"><span class="metric-label">Comentários no total</span><strong><?= count($userComments) ?></strong><span class="metric-foot">Inclui registros moderados →</span></a>
    <a class="user-metric" href="#atividade-recente"><span class="metric-label">Ações registradas</span><strong><?= $userAuditTotal ?></strong><span class="metric-foot">Últimas ações mostradas abaixo →</span></a>
    <a class="user-metric" href="<?= e(url('catalogo.php')) ?>"><span class="metric-label">Catálogo</span><strong><?= count($d['works']) ?></strong><span class="metric-foot">Obras disponíveis na wiki →</span></a>
  </div>
  <div class="user-dashboard-columns">
    <section class="panel" id="atividade-avaliacoes"><div class="eyebrow">DADOS PESSOAIS</div><h3>Distribuição da sua atividade</h3><div class="user-bars">
      <?php $maxMetric=max(1,count($seen),count($userRatings),count($userComments)); foreach([['Obras assistidas',count($seen)],['Avaliações',count($userRatings)],['Comentários',count($userComments)]] as [$label,$value]): ?>
      <div class="user-bar-row"><span><?= e($label) ?></span><span class="user-bar-track"><span class="user-bar-fill" style="width:<?= (int)round($value/$maxMetric*100) ?>%"></span></span><strong><?= (int)$value ?></strong></div>
      <?php endforeach; ?>
    </div><div class="user-shortcuts"><a class="btn ghost" href="#assistidos">Ver histórico pessoal</a><a class="btn ghost" href="<?= e(url('catalogo.php')) ?>">Explorar catálogo</a></div></section>
    <section class="panel" id="atividade-recente"><div class="eyebrow">REGISTRO DA CONTA</div><h3>Atividade recente</h3>
      <?php if ($userAudit): foreach($userAudit as $a): ?><article class="user-activity"><div class="eyebrow"><?= e($a['at'] ?? '') ?> · <?= e($a['action'] ?? 'Ação') ?></div><p><?= e($a['details'] ?? 'Atividade registrada') ?></p></article><?php endforeach; else: ?><p class="empty">Ainda não há ações no histórico para exibir. As próximas ações registradas aparecerão aqui.</p><?php endif; ?>
    </section>
  </div>
</section>
<div class="profile-lists">
  <section class="panel" id="assistidos">
    <div class="eyebrow">ARQUIVO PESSOAL // 01</div>
    <h2>O que eu já vi</h2><?php if ($seen): ?><div class="mini-list"><?php foreach ($seen as $w): ?><a href="<?= work_path($w) ?>"><?= e($w['title']) ?><span>Assistido</span></a><?php endforeach; ?></div><?php else: ?><div class="empty">Você ainda não marcou nenhuma obra como assistida.</div><?php endif; ?>
  </section>
  <section class="panel" id="assistindo">
    <div class="eyebrow">ARQUIVO PESSOAL // 02</div>
    <h2>O que estou vendo</h2><?php if ($watching): ?><div class="mini-list"><?php foreach ($watching as $w): ?><a href="<?= work_path($w) ?>"><?= e($w['title']) ?><span>Assistindo</span></a><?php endforeach; ?></div><?php else: ?><div class="empty">Você ainda não marcou nenhuma obra como em andamento.</div><?php endif; ?>
  </section>
</div>
<section class="panel" id="meus-comentarios" style="margin-top:22px">
  <div class="eyebrow">CONTRIBUIÇÕES // COMENTÁRIOS</div><h2>Meus comentários</h2>
  <?php $myRecentComments = array_slice(array_reverse($userComments), 0, 8); if ($myRecentComments): ?>
    <div class="mini-list"><?php foreach ($myRecentComments as $cm): $cw = find_work($cm['work_id'] ?? 0); ?><div><b><?= e($cw['title'] ?? 'Obra removida') ?></b><span><?= e($cm['status'] ?? 'published') ?></span><p><?= e($cm['body'] ?? '') ?></p></div><?php endforeach; ?></div>
  <?php else: ?><div class="empty">Você ainda não publicou comentários.</div><?php endif; ?>
</section>
<section class="panel" id="minha-lista" style="margin-top:22px">
  <div class="eyebrow">ARQUIVO PESSOAL // 03</div>
  <h2>Minha lista</h2><?php if ($saved): ?><div class="mini-list"><?php foreach ($saved as $x): ?><a href="<?= e(work_path($x)) ?>"><?= e($x['title']) ?><span>Adicionado à lista</span></a><?php endforeach; ?></div><?php else: ?><div class="empty">Sua lista ainda está vazia.</div><?php endif; ?>
</section>
<div class="profile-footer-actions"><a class="btn ghost" href="<?= e(url('catalogo.php')) ?>">Voltar ao catálogo</a><a class="btn danger" href="<?= e(url('login/logout.php')) ?>">Sair</a></div>
<?php $content = ob_get_clean();
$content .= '<script>(function(){const i=document.getElementById("avatar-input"),b=document.getElementById("avatar-preview");if(!i||!b)return;const old=document.querySelector(".profile-card .profile-avatar img");if(old)b.innerHTML=old.outerHTML;else b.textContent="Prévia da foto";i.addEventListener("change",function(){const f=this.files&&this.files[0];if(!f)return;if(!["image/jpeg","image/png","image/webp"].includes(f.type)||f.size>2097152){alert("Escolha JPG, PNG ou WEBP de até 2 MB.");this.value="";return;}const im=document.createElement("img");im.alt="Pré-visualização da foto";im.src=URL.createObjectURL(f);b.replaceChildren(im);});})();</script>';
shell($content, $active);
require __DIR__ . '/../includes/footer.php'; ?>