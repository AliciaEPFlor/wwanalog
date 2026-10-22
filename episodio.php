<?php
/* WWAnalog — Detalhes de episódio: exibe a obra relacionada, conteúdo e interações. */ require_once __DIR__ . '/includes/functions.php';
require_login();
$w = find_work($_GET['obra'] ?? '');
$idx = (int)($_GET['ep'] ?? -1);
if (!$w || !isset($w['episodes'][$idx])) {
  http_response_code(404);
  exit('Episódio não encontrado.');
}
$ep = $w['episodes'][$idx];
$active = 'catalog';
$page_title = $ep['title'] ?? 'Episódio';
check_csrf();
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $d = data_read();
  if (isset($_POST['comment'])) {
    $body = trim($_POST['comment']);
    if ($body === '') $err = 'Escreva um comentário.';
    else {
      $d['comments'][] = ['id' => max(array_column($d['comments'], 'id') ?: [0]) + 1, 'user_id' => (int)user()['id'], 'user_name' => user()['nickname'] ?: user()['name'], 'work_id' => (int)$w['id'], 'episode_index' => $idx, 'body' => $body, 'status' => 'published', 'created_at' => date('c')];
      data_write($d);
      audit_event('comentario_episodio', $w['title'] . ' / ' . ($ep['title'] ?? 'Episódio'));
      $msg = 'Comentário publicado.';
    }
  } elseif (isset($_POST['stars'])) {
    $stars = (int)$_POST['stars'];
    if ($stars >= 1 && $stars <= 5) {
      $d['ratings'][] = ['user_id' => (int)user()['id'], 'work_id' => (int)$w['id'], 'episode_index' => $idx, 'stars' => $stars, 'created_at' => date('c')];
      data_write($d);
      $msg = 'Avaliação salva.';
    }
  }
}
$d = data_read();
$comments = work_comments($w['id'], $idx);
$video = $ep['embed_url'] ?? '';
$source = $ep['url'] ?? '';
$localVideo = '';
if (!empty($ep['local_video'])) {
  $candidate = __DIR__ . '/' . ltrim((string)$ep['local_video'], '/');
  if (is_file($candidate)) $localVideo = url((string)$ep['local_video']);
}
if (!$video && $source) {
  $videoId = '';
  $parts = parse_url($source);
  if (($parts['host'] ?? '') === 'youtu.be') $videoId = trim($parts['path'] ?? '', '/');
  if (in_array(($parts['host'] ?? ''), ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
    parse_str($parts['query'] ?? '', $query);
    if (!empty($query['v'])) $videoId = $query['v'];
    if (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]+)~', $parts['path'] ?? '', $match)) $videoId = $match[1];
  }
  if (preg_match('~^[A-Za-z0-9_-]{6,}$~', $videoId)) $video = 'https://www.youtube.com/embed/' . $videoId . '?rel=0';
}
$active = 'catalog';
require __DIR__ . '/includes/header.php';
ob_start(); ?><div class="breadcrumbs"><a href="<?= e(url('catalogo.php')) ?>">Catálogo</a> / <a href="<?= e(url('obra.php?id=' . (int)$w['id'])) ?>"><?= e($w['title']) ?></a> / <a href="<?= e(url('episodios.php?obra=' . (int)$w['id'])) ?>">Episódios</a></div>
<div class="section-head">
  <div>
    <div class="eyebrow">EPISÓDIO <?= str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT) ?></div>
    <h1><?= e($ep['title'] ?? 'Episódio') ?></h1>
    <small>
      <?= e($w['title']) ?> · <?= e($ep['duration'] ?? 'Duração não informada') ?>
  </small>
  </div>
</div>
<?php if ($msg): ?>
  <div class="notice success"><?= e($msg) ?>
</div>
<?php endif; ?><?php if ($err): ?>
  <div class="notice"><?= e($err) ?>
</div>
<?php endif; ?><div class="video-box">
  <?php if ($localVideo && preg_match('~^media/episodes/[A-Za-z0-9_./-]+\.(?:mp4|webm|ogv|m4v)$~i', (string)$ep['local_video'])): ?>
    <video class="episode-video" controls preload="metadata" playsinline>
      <source src="<?= e($localVideo) ?>" type="<?= str_ends_with(strtolower($localVideo), '.webm') ? 'video/webm' : (str_ends_with(strtolower($localVideo), '.ogv') ? 'video/ogg' : 'video/mp4') ?>">Seu navegador não suporta reprodução de vídeo.
    </video><?php elseif ($video && preg_match('~^https://www.youtube.com/embed/[A-Za-z0-9_-]+(?:\?rel=0)?$~', $video)): ?>
      <iframe src="<?= e($video) ?>" title="<?= e($ep['title'] ?? 'Player do episódio') ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
      <?php else: ?>PLAYER INDISPONÍVEL OU INCORPORAÇÃO NÃO AUTORIZADA<br>
        <small>O vídeo pode estar indisponível ou bloquear a incorporação. Tente novamente mais tarde.
        </small>
        <?php endif; ?>
      </div>
<div class="hero-actions">
  <a class="btn ghost" href="<?= e(url('episodios.php?obra=' . (int)$w['id'])) ?>">Lista de episódios</a>
  <a class="btn ghost" href="<?= e(url('obra.php?id=' . (int)$w['id'])) ?>">Detalhes da obra</a>
  <a class="btn ghost" href="<?= e(url('catalogo.php')) ?>">Catálogo</a></div>
<section class="panel" style="margin-top:24px">
  <h2>Comentários deste episódio</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <label>Comentário</label>
    <textarea class="textarea" name="comment" rows="3" required></textarea><button class="btn" style="margin-top:8px">Publicar</button></form>
  <form method="post" style="margin-top:14px"><input type="hidden" name="csrf" value="<?= csrf() ?>">
  <label>Avaliação do episódio</label><select class="select" name="stars">
      <option value="5">5 estrelas</option>
      <option value="4">4 estrelas</option>
      <option value="3">3 estrelas</option>
      <option value="2">2 estrelas</option>
      <option value="1">1 estrela</option>
    </select>
    <button class="btn ghost" style="margin-top:8px">Avaliar</button></form><?php foreach ($comments as $c): ?>
      <article class="comment">
      <div class="eyebrow"><?= e($c['user_name'] ?? 'Usuário') ?></div>
      <p><?= nl2br(e($c['body'] ?? '')) ?></p>
    </article>
<?php endforeach; ?>
</section><?php $content = ob_get_clean();

shell($content, $active);
require __DIR__ . '/includes/footer.php'; ?>