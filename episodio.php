<?php
/* WWAnalog — Detalhes de episódio: exibe a obra relacionada, conteúdo e interações. */

// Carrega as funções auxiliares do sistema (helpers, validações, etc.)
require_once __DIR__ . '/includes/functions.php';

// Exige que o visitante esteja logado. Se não estiver, manda para a tela de login.
require_login();

// Procura a obra pelo parâmetro "obra" da URL. Se não existir, retorna vazio.
$w = find_work($_GET['obra'] ?? '');

// Pega o número do episódio na URL ("ep"). Converte para inteiro (-1 = inválido).
$idx = (int)($_GET['ep'] ?? -1);

// Se a obra não existe OU o episódio não existe, mostra erro 404 e para tudo.
// É como: "essa página não existe".
if (!$w || !isset($w['episodes'][$idx])) {
  http_response_code(404);
  exit('Episódio não encontrado.');
}

// Guarda os dados do episódio atual numa variável curta para facilitar
$ep = $w['episodes'][$idx];

// Diz ao menu qual aba deve ficar destacada (a aba "Catálogo")
$active = 'catalog';

// Define o título da página (o nome do episódio, ou "Episódio" se não tiver)
$page_title = $ep['title'] ?? 'Episódio';

// Verifica o "csrf" (uma proteção de segurança que impede envios falsos de formulário)
check_csrf();

// Prepara variáveis para guardar mensagens de sucesso e de erro
$msg = '';
$err = '';

// ===== Só entra aqui se o visitante enviou um formulário (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Lê os dados atualizados do "estoque" (arquivo de dados)
  $d = data_read();

  // ---- Caso 1: o visitante enviou um COMENTÁRIO ----
  if (isset($_POST['comment'])) {
    $body = trim($_POST['comment']);  // Pega o texto e tira espaços extras

    if ($body === '') {
      // Se o comentário estiver vazio, mostra erro
      $err = 'Escreva um comentário.';
    } else {
      // Senão, cria um novo comentário e adiciona na lista de comentários:
      //  - gera um novo ID (maior ID existente + 1)
      //  - guarda quem comentou (ID e nome/apelido)
      //  - guarda a obra e o episódio
      //  - guarda o texto e a data/hora atual
      $d['comments'][] = [
        'id'            => max(array_column($d['comments'], 'id') ?: [0]) + 1,
        'user_id'       => (int)user()['id'],
        'user_name'     => user()['nickname'] ?: user()['name'],
        'work_id'       => (int)$w['id'],
        'episode_index' => $idx,
        'body'          => $body,
        'status'        => 'published',
        'created_at'    => date('c')
      ];

      // Salva no "estoque"
      data_write($d);

      // Registra no histórico do sistema (auditoria)
      audit_event('comentario_episodio', $w['title'] . ' / ' . ($ep['title'] ?? 'Episódio'));

      // Mensagem de sucesso
      $msg = 'Comentário publicado.';
    }
  }

  // ---- Caso 2: o visitante enviou uma AVALIAÇÃO (estrelas) ----
  elseif (isset($_POST['stars'])) {
    $stars = (int)$_POST['stars'];  // Pega a nota (1 a 5)

    if ($stars >= 1 && $stars <= 5) {
      // Adiciona a avaliação na lista de avaliações
      $d['ratings'][] = [
        'user_id'       => (int)user()['id'],
        'work_id'       => (int)$w['id'],
        'episode_index' => $idx,
        'stars'         => $stars,
        'created_at'    => date('c')
      ];
      data_write($d);       // Salva no "estoque"
      $msg = 'Avaliação salva.';  // Mensagem de sucesso
    }
  }
}

// Relê os dados atualizados (para pegar os novos comentários já salvos)
$d = data_read();

// Busca só os comentários deste episódio
$comments = work_comments($w['id'], $idx);

// ===== Descobrir qual vídeo exibir no player =====

// Se o episódio tem um link de embed já pronto, usa
$video = $ep['embed_url'] ?? '';

// Se tem um link "normal" (fonte), usa
$source = $ep['url'] ?? '';

// Variável para o vídeo local (arquivo no próprio servidor)
$localVideo = '';

// Se o episódio tem um arquivo de vídeo local cadastrado...
if (!empty($ep['local_video'])) {
  // Monta o caminho completo do arquivo no servidor
  $candidate = __DIR__ . '/' . ltrim((string)$ep['local_video'], '/');

  // Só usa se o arquivo realmente existe
  if (is_file($candidate)) $localVideo = url((string)$ep['local_video']);
}

// Se não tem embed pronto, MAS tem um link "normal", tenta transformar em YouTube embed
if (!$video && $source) {
  $videoId = '';                               // ID do vídeo no YouTube

  $parts = parse_url($source);                 // Quebra o link em partes

  // Caso 1: link curto do tipo youtu.be/XXXXXXX
  if (($parts['host'] ?? '') === 'youtu.be') $videoId = trim($parts['path'] ?? '', '/');

  // Caso 2: links do youtube.com (normal, mobile, etc.)
  if (in_array(($parts['host'] ?? ''), ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
    parse_str($parts['query'] ?? '', $query);  // Lê os parâmetros da URL
    if (!empty($query['v'])) $videoId = $query['v'];  // ?v=XXXXX
    // Caso 3: links do tipo /embed/XXXXX ou /shorts/XXXXX
    if (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]+)~', $parts['path'] ?? '', $match)) $videoId = $match[1];
  }

  // Se encontrou um ID válido, monta o link de embed do YouTube
  if (preg_match('~^[A-Za-z0-9_-]{6,}$~', $videoId))
    $video = 'https://www.youtube.com/embed/' . $videoId . '?rel=0';
}

// Redefine a aba ativa (por garantia) e carrega o topo do site
$active = 'catalog';
require __DIR__ . '/includes/header.php';

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho
ob_start();
?>

<!-- Migalhas de pão: mostra o caminho (Catálogo / Obra / Episódios) -->
<div class="breadcrumbs">
  <a href="<?= e(url('catalogo.php')) ?>">Catálogo</a> /
  <a href="<?= e(url('obra.php?id=' . (int)$w['id'])) ?>"><?= e($w['title']) ?></a> /
  <a href="<?= e(url('episodios.php?obra=' . (int)$w['id'])) ?>">Episódios</a>
</div>

<!-- Cabeçalho do episódio: número, título e dados da obra -->
<div class="section-head">
  <div>
    <!-- Número do episódio formatado com 2 dígitos (01, 02, ...) -->
    <div class="eyebrow">EPISÓDIO <?= str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT) ?></div>
    <h1><?= e($ep['title'] ?? 'Episódio') ?></h1>
    <small>
      <?= e($w['title']) ?> · <?= e($ep['duration'] ?? 'Duração não informada') ?>
    </small>
  </div>
</div>

<!-- Se houve sucesso, mostra mensagem verde -->
<?php if ($msg): ?>
  <div class="notice success"><?= e($msg) ?></div>
<?php endif; ?>

<!-- Se houve erro, mostra mensagem de erro -->
<?php if ($err): ?>
  <div class="notice"><?= e($err) ?></div>
<?php endif; ?>

<!-- Caixa do player de vídeo -->
<div class="video-box">

  <!-- Opção 1: vídeo LOCAL (arquivo no próprio servidor) -->
  <?php if ($localVideo && preg_match('~^media/episodes/[A-Za-z0-9_./-]+\.(?:mp4|webm|ogv|m4v)$~i', (string)$ep['local_video'])): ?>
    <video class="episode-video" controls preload="metadata" playsinline>
      <!-- O "type" depende da extensão do arquivo (mp4, webm ou ogv) -->
      <source src="<?= e($localVideo) ?>" type="<?= str_ends_with(strtolower($localVideo), '.webm') ? 'video/webm' : (str_ends_with(strtolower($localVideo), '.ogv') ? 'video/ogg' : 'video/mp4') ?>">
      Seu navegador não suporta reprodução de vídeo.
    </video>

  <!-- Opção 2: vídeo do YouTube (iframe). Só aceita links no formato seguro do YouTube -->
  <?php elseif ($video && preg_match('~^https://www.youtube.com/embed/[A-Za-z0-9_-]+(?:\?rel=0)?$~', $video)): ?>
    <iframe src="<?= e($video) ?>" title="<?= e($ep['title'] ?? 'Player do episódio') ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>

  <!-- Opção 3: nenhum player disponível -->
  <?php else: ?>
    PLAYER INDISPONÍVEL OU INCORPORAÇÃO NÃO AUTORIZADA<br>
    <small>O vídeo pode estar indisponível ou bloquear a incorporação. Tente novamente mais tarde.</small>
  <?php endif; ?>
</div>

<!-- Botões de atalho para outras telas -->
<div class="hero-actions">
  <a class="btn ghost" href="<?= e(url('episodios.php?obra=' . (int)$w['id'])) ?>">Lista de episódios</a>
  <a class="btn ghost" href="<?= e(url('obra.php?id=' . (int)$w['id'])) ?>">Detalhes da obra</a>
  <a class="btn ghost" href="<?= e(url('catalogo.php')) ?>">Catálogo</a>
</div>

<!-- Painel de comentários e avaliações -->
<section class="panel" style="margin-top:24px">
  <h2>Comentários deste episódio</h2>

  <!-- Formulário de comentário -->
  <form method="post">
    <!-- Campo escondido com o token de segurança (CSRF) -->
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <label>Comentário</label>
    <textarea class="textarea" name="comment" rows="3" required></textarea>
    <button class="btn" style="margin-top:8px">Publicar</button>
  </form>

  <!-- Formulário de avaliação (estrelas) -->
  <form method="post" style="margin-top:14px">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <label>Avaliação do episódio</label>
    <select class="select" name="stars">
      <option value="5">5 estrelas</option>
      <option value="4">4 estrelas</option>
      <option value="3">3 estrelas</option>
      <option value="2">2 estrelas</option>
      <option value="1">1 estrela</option>
    </select>
    <button class="btn ghost" style="margin-top:8px">Avaliar</button>
  </form>

  <!-- Lista os comentários já existentes -->
  <?php foreach ($comments as $c): ?>
    <article class="comment">
      <!-- Nome de quem comentou -->
      <div class="eyebrow"><?= e($c['user_name'] ?? 'Usuário') ?></div>
      <!-- Texto do comentário. nl2br transforma quebras de linha em <br> -->
      <p><?= nl2br(e($c['body'] ?? '')) ?></p>
    </article>
  <?php endforeach; ?>
</section>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML)
require __DIR__ . '/includes/footer.php';
?>