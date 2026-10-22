<?php
/* WWAnalog — Detalhes de uma obra: apresenta metadados, avaliações, comentários e listas. */

// Carrega as funções auxiliares do sistema (helpers, validações, etc.)
require_once __DIR__ . '/includes/functions.php';

// Procura a obra pelo parâmetro "id" da URL. Se não existir, retorna vazio.
$w = find_work($_GET['id'] ?? '');

// Se a obra não existe, mostra erro 404 e para tudo
if (!$w) {
    http_response_code(404);
    exit('Obra não encontrada.');
}

// Se a pessoa tentou ENVIAR um formulário mas NÃO está logada,
// manda ela para a tela de login e para por aqui
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !logged()) {
    header('Location: ' . url('login/login.php'));
    exit;
}

// Diz ao menu qual aba deve ficar destacada (a aba "Catálogo")
$active = 'catalog';

// Define o título da página como o nome da obra
$page_title = $w['title'];

// ===== REGISTRO DE VISUALIZAÇÃO =====
// Lê o "estoque" de dados
$vd = data_read();

// Registra que alguém abriu a página desta obra.
// Guarda: tipo do evento, ID da obra, ID do usuário (0 se não logado) e data/hora.
// Isso serve para estatísticas de acesso.
$vd['views'][] = [
    'type'    => 'work',
    'work_id' => (int)$w['id'],
    'user_id' => (int)(user()['id'] ?? 0),
    'at'      => date('c')
];

// Salva o registro no "estoque"
data_write($vd);

// Carrega o topo do site (logo, menu, etc.)
require __DIR__ . '/includes/header.php';

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário
check_csrf();

// Prepara variáveis para mensagens de sucesso e erro
$msg = '';
$err = '';

// ===== SÓ ENTRA AQUI SE O USUÁRIO ENVIOU UM FORMULÁRIO (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Exige login (por segurança)
    require_login();

    // Lê os dados atualizados do "estoque"
    $d = data_read();

    // Pega o ID do usuário logado
    $uid = (int)user()['id'];

    // ---- Caso 1: o usuário quer mudar o status na LISTA PESSOAL ----
    if (isset($_POST['list_status'])) {
        $status = $_POST['list_status'];

        // Só aceita esses três valores (segurança contra valores estranhos)
        $allowed = ['Adicionado à lista', 'Assistindo', 'Assistido'];

        if (in_array($status, $allowed, true)) {
            $found = false;

            // Procura se o usuário já tem essa obra na lista dele
            // O "&" antes de $l permite alterar o item dentro do laço
            foreach ($d['lists'] as &$l) {
                if ((int)($l['user'] ?? 0) === $uid && (int)($l['work'] ?? 0) === (int)$w['id']) {
                    $l['status'] = $status;  // Atualiza o status
                    $found = true;
                    break;
                }
            }
            unset($l); // Boa prática: desfaz a referência após o laço

            // Se não encontrou, adiciona uma nova entrada na lista
            if (!$found) {
                $d['lists'][] = [
                    'user'       => $uid,
                    'work'       => (int)$w['id'],
                    'status'     => $status,
                    'updated_at' => date('c')
                ];
            }

            // Salva no "estoque"
            data_write($d);

            // Registra no histórico do sistema (auditoria)
            audit_event('lista_atualizada', $w['title'] . ' — ' . $status);

            $msg = 'Sua lista foi atualizada.';
        }
    }

    // ---- Caso 2: o usuário enviou uma AVALIAÇÃO (estrelas) ----
    elseif (isset($_POST['stars'])) {
        $stars = (int)$_POST['stars'];

        // Só aceita notas de 1 a 5
        if ($stars >= 1 && $stars <= 5) {
            $found = false;

            // Procura se o usuário JÁ avaliou esta obra (avaliação geral, sem episódio)
            foreach ($d['ratings'] as &$r) {
                if ((int)($r['user_id'] ?? 0) === $uid
                    && (int)($r['work_id'] ?? 0) === (int)$w['id']
                    && !isset($r['episode_index'])) {  // Só conta avaliação da OBRA inteira
                    $r['stars']      = $stars;           // Atualiza a nota
                    $r['updated_at'] = date('c');
                    $found = true;
                    break;
                }
            }
            unset($r);

            // Se não encontrou avaliação anterior, cria uma nova
            if (!$found) {
                $d['ratings'][] = [
                    'user_id'    => $uid,
                    'work_id'    => (int)$w['id'],
                    'stars'      => $stars,
                    'created_at' => date('c')
                ];
            }

            data_write($d);
            $msg = 'Sua avaliação foi salva.';
        }
    }

    // ---- Caso 3: o usuário enviou um COMENTÁRIO ----
    elseif (isset($_POST['comment'])) {
        $body = trim($_POST['comment']);

        if ($body === '') {
            $err = 'Escreva um comentário antes de enviar.';
        } elseif (mb_strlen($body) > 2000) {
            $err = 'O comentário deve ter no máximo 2.000 caracteres.';
        } else {
            // Cria um novo comentário e adiciona na lista de comentários:
            //  - novo ID (maior ID existente + 1)
            //  - quem comentou
            //  - qual obra
            //  - texto e data/hora
            $d['comments'][] = [
                'id'         => max(array_column($d['comments'], 'id') ?: [0]) + 1,
                'user_id'    => $uid,
                'user_name'  => user()['nickname'] ?: user()['name'],
                'work_id'    => (int)$w['id'],
                'body'       => $body,
                'status'     => 'published',
                'created_at' => date('c')
            ];

            data_write($d);

            // Registra no histórico do sistema
            audit_event('comentario_criado', $w['title']);

            $msg = 'Comentário publicado.';
        }
    }
}

// ===== PREPARAÇÃO DOS DADOS PARA MOSTRAR NA TELA =====

// Relê os dados atualizados (para pegar o que acabou de ser salvo)
$d = data_read();

// Calcula a média de avaliação da obra (nota média das estrelas)
$avg = work_rating_average($w['id']);

// Busca os comentários da obra SOMENTE se o usuário estiver logado
// (visitantes não logados não veem comentários)
$comments = logged() ? work_comments($w['id']) : [];

// Descobre qual nota o usuário logado já deu para esta obra (0 = ainda não avaliou)
$myStars = 0;
foreach ($d['ratings'] as $r) {
    if ((int)($r['user_id'] ?? 0) === (int)(user()['id'] ?? 0)
        && (int)($r['work_id'] ?? 0) === (int)$w['id']
        && !isset($r['episode_index'])) {  // Só avaliação da obra inteira
        $myStars = (int)$r['stars'];
    }
}

// Pega a imagem da capa e a lista de episódios
$img = $w['image'] ?? '';
$episodes = $w['episodes'] ?? [];

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho
ob_start();
?>

<!-- Migalhas de pão: mostra o caminho (Catálogo / Obra) -->
<div class="breadcrumbs">
    <a href="<?= e(url('catalogo.php')) ?>">Catálogo</a> / <?= e($w['title']) ?>
</div>

<!-- Se o usuário acabou de se cadastrar, mostra mensagem de boas-vindas -->
<?php if (isset($_GET['cadastro'])): ?>
    <div class="notice success">Cadastro concluído! Você já está conectado.</div>
<?php endif; ?>

<!-- ===== CABEÇALHO DE DETALHES DA OBRA ===== -->
<div class="detail-head">

    <!-- Área da capa: se tem imagem, mostra; se não, mostra "ARQUIVO / SEM CAPA" -->
    <div class="cover detail-cover <?= e(cover_class($w)) ?>">
        <?php if ($img): ?>
            <img src="<?= e(preg_match('~^https?://~i', $img) ? $img : url($img)) ?>"
                 alt="Capa de <?= e($w['title']) ?>"
                 style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
            ARQUIVO / SEM CAPA
        <?php endif; ?>
    </div>

    <!-- Coluna com informações detalhadas -->
    <div class="detail">

        <!-- Número do registro (ex.: REGISTRO #0042) -->
        <div class="eyebrow">REGISTRO #<?= str_pad((string)$w['id'], 4, '0', STR_PAD_LEFT) ?></div>

        <!-- Título da obra -->
        <h1><?= e($w['title']) ?></h1>

        <!-- Tags/etiquetas: tag principal, categoria e status (se existir) -->
        <div class="detail-meta">
            <span class="tag"><?= e($w['tag'] ?? 'Analog Horror') ?></span>
            <span class="tag"><?= e(category_name($w['category'])) ?></span>
            <?php if (!empty($w['status'])): ?>
                <span class="tag"><?= e($w['status']) ?></span>
            <?php endif; ?>
        </div>

        <!-- Descrição curta. Se não tiver, mostra texto padrão -->
        <p class="desc"><?= e($w['description'] ?: 'Sinopse ainda não cadastrada.') ?></p>

        <!-- Se tiver sinopse completa, mostra num bloco separado -->
        <?php if (!empty($w['synopsis'])): ?>
            <h3>Sinopse completa</h3>
            <p><?= nl2br(e($w['synopsis'])) ?></p>
        <?php endif; ?>

        <!-- Se tiver história da obra, mostra num bloco separado -->
        <?php if (!empty($w['story'])): ?>
            <h3>História da obra</h3>
            <p><?= nl2br(e($w['story'])) ?></p>
        <?php endif; ?>

        <!-- Se tiver aviso de conteúdo sensível, mostra um alerta -->
        <?php if (!empty($w['warning'])): ?>
            <div class="notice">⚠ <strong>Aviso de conteúdo:</strong> <?= e($w['warning']) ?></div>
        <?php endif; ?>

        <!-- ===== PAINEL COM FICHA TÉCNICA ===== -->
        <div class="panel" style="margin-top:16px;padding:14px">
            <!-- Cada "kv" é um par "chave : valor" -->
            <div class="kv"><b>Título original</b> <span><?= e($w['original_title'] ?? $w['title']) ?></span></div>
            <div class="kv"><b>Criador</b> <span><?= e($w['creator'] ?: 'Não cadastrado') ?></span></div>
            <div class="kv"><b>Canal do criador</b> <span><?= e($w['creator'] ?: 'Não informado') ?></span></div>
            <div class="kv"><b>Ano</b> <span><?= e($w['year'] ?: 'Não cadastrado') ?></span></div>
            <div class="kv"><b>Episódios cadastrados</b> <span><?= count($episodes) ?></span></div>
            <div class="kv"><b>Situação</b> <span><?= e($w['status'] ?: 'Não cadastrada') ?></span></div>
            <div class="kv"><b>Classificação indicativa</b> <span><?= e($w['rating'] ?? 'Não informada') ?></span></div>
        </div>

        <!-- Botão "Clique aqui para assistir!".
             Se logado: vai direto para a lista de episódios.
             Se NÃO logado: vai para o login e, depois, volta para a lista de episódios. -->
        <div class="hero-actions">
            <a class="btn" href="<?= logged() ? '/episodios.php?obra=' . (int)$w['id'] : '/login/login.php?return=' . urlencode('/episodios.php?obra=' . (int)$w['id']) ?>">Clique aqui para assistir!</a>
            <?php if (!logged()): ?>
                <span class="small muted">É necessário entrar ou criar uma conta para assistir e interagir.</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mensagens de sucesso/erro (se houver) -->
<?php if ($msg): ?><div class="notice success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice"><?= e($err) ?></div><?php endif; ?>

<!-- ===== SEÇÃO DE COMENTÁRIOS ===== -->
<section class="panel" style="margin-top:24px">
    <div class="section-head">
        <div>
            <h2>Comentários sobre a obra</h2>
            <small>Área exclusiva para usuários autenticados</small>
        </div>
    </div>

    <!-- Se NÃO está logado: mostra aviso e botão de login -->
    <?php if (!logged()): ?>
        <p>Entre ou crie uma conta para ler e publicar comentários desta obra.</p>
        <a class="btn" href="<?= e(url('login/login.php')) ?>">Entrar</a>

    <!-- Se ESTÁ logado: mostra formulário + lista de comentários -->
    <?php else: ?>

        <!-- Formulário de novo comentário -->
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <label for="comment">Seu comentário</label>
            <textarea class="textarea" id="comment" name="comment" maxlength="2000" rows="4" required></textarea>
            <button class="btn" style="margin-top:10px">Publicar comentário</button>
        </form>

        <!-- Lista de comentários já publicados -->
        <div class="comment-list" style="margin-top:18px">
            <?php if ($comments): foreach ($comments as $c): ?>
                <article class="comment">
                    <!-- Nome de quem comentou + data -->
                    <div class="eyebrow"><?= e($c['user_name'] ?? 'Usuário') ?> · <?= e(date('d/m/Y', strtotime($c['created_at'] ?? 'now'))) ?></div>
                    <!-- Texto do comentário (nl2br transforma quebras de linha em <br>) -->
                    <p><?= nl2br(e($c['body'] ?? '')) ?></p>
                </article>
            <?php endforeach;
            else: ?>
                <div class="empty">Nenhum comentário publicado ainda.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML)
require __DIR__ . '/includes/footer.php';
?>