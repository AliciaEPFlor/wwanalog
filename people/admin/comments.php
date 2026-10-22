<?php
/* WWAnalog — Gerenciamento legado e moderação de comentários. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../../" porque este arquivo está dentro de uma subpasta (people/admin/).
require_once __DIR__ . '/../../includes/functions.php';

// Exige que o usuário logado seja ADMINISTRADOR.
// Se não for, o sistema bloqueia o acesso automaticamente.
require_admin();

// Define o título da página e marca "admin" como aba ativa do menu
$page_title = 'Moderação de comentários';
$active = 'admin';

// Carrega o topo do site (logo, menu, etc.)
require __DIR__ . '/../../includes/header.php';

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário
check_csrf();

// Lê os dados do "estoque"
$d = data_read();

// Prepara uma variável para guardar a mensagem de sucesso
$msg = '';

// ===== SÓ ENTRA AQUI SE O ADMIN ENVIOU UM FORMULÁRIO (POST) =====
// Isso acontece quando ele clica em "Atualizar status" de um comentário.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pega o ID do comentário que ele quer alterar
    $id = (int)($_POST['comment_id'] ?? 0);

    // Percorre TODOS os comentários procurando o que tem esse ID.
    // O "&" antes de $c permite alterar o item dentro do laço.
    foreach ($d['comments'] as &$c) {
        if ((int)($c['id'] ?? 0) === $id) {

            // Atualiza o status do comentário para o que o admin escolheu
            // (published, pending, flagged ou removed)
            $c['status'] = $_POST['status'] ?? 'published';

            // Prepara a mensagem de sucesso
            $msg = 'Status do comentário atualizado.';

            // Para de procurar (já achou)
            break;
        }
    }
    unset($c); // Boa prática: desfaz a referência após o laço

    // Se algo foi alterado, salva no "estoque" e registra na auditoria
    if ($msg) {
        data_write($d);
        audit_event('moderacao_comentario', 'Comentário ' . $id . ' => ' . ($_POST['status'] ?? ''));
    }

    // Relê os dados atualizados
    $d = data_read();
}

// ===== FILTROS DA LISTA DE COMENTÁRIOS =====

// Lê o texto de busca, o status e a obra escolhidos nos filtros (via URL)
$q = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterWork = (string)($_GET['work_id'] ?? '');

// Filtra a lista de comentários aplicando os critérios escolhidos.
// Cada condição só restringe se o filtro estiver preenchido.
$filteredComments = array_values(array_filter($d['comments'], function ($c) use ($d, $q, $filterStatus, $filterWork) {

    // Descobre a qual obra o comentário pertence (para poder buscar pelo título dela)
    $w = find_work($c['work_id'] ?? 0);

    // Filtro 1: busca textual — procura no nome do usuário, no texto e no título da obra
    if ($q !== '' && stripos(($c['user_name'] ?? '') . ' ' . ($c['body'] ?? '') . ' ' . ($w['title'] ?? ''), $q) === false) return false;

    // Filtro 2: status — se escolheu um status, só mostra os que têm esse status
    if ($filterStatus !== '' && ($c['status'] ?? 'published') !== $filterStatus) return false;

    // Filtro 3: obra — se escolheu uma obra, só mostra comentários dela
    if ($filterWork !== '' && (string)($c['work_id'] ?? '') !== $filterWork) return false;

    // Se passou por todos os filtros, mantém o comentário
    return true;
}));
?>

<!-- Título da página -->
<h1>Moderação de comentários</h1>

<!-- Se houve alteração bem-sucedida, mostra mensagem verde -->
<?php if ($msg): ?>
    <div class="notice success"><?= e($msg) ?></div>
<?php endif; ?>

<!-- ===== FORMULÁRIO DE FILTROS ===== -->
<form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0">

    <!-- Campo de busca (texto livre) -->
    <input class="input" style="flex:1;min-width:190px" name="q" value="<?= e($q) ?>" placeholder="Buscar comentário, usuário ou obra">

    <!-- Menu de obras -->
    <select class="select" name="work_id">
        <option value="">Todas as obras</option>
        <?php foreach ($d['works'] as $work): ?>
            <option value="<?= e($work['id']) ?>" <?= $filterWork === (string)$work['id'] ? 'selected' : '' ?>><?= e($work['title']) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Menu de status: publicado, aguardando, sinalizado ou removido -->
    <select class="select" name="status">
        <option value="">Todos os status</option>
        <?php foreach (['published' => 'Publicado', 'pending' => 'Aguardando análise', 'flagged' => 'Sinalizado', 'removed' => 'Removido'] as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $filterStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Botões: aplicar filtros e limpar -->
    <button class="btn">Filtrar</button>
    <a class="btn ghost" href="<?= e(url('people/admin/comments.php')) ?>">Limpar</a>
</form>

<!-- Quantos comentários foram encontrados com os filtros atuais -->
<p class="small muted"><?= count($filteredComments) ?> comentário(s) encontrado(s).</p>

<!-- ===== LISTA DE COMENTÁRIOS ===== -->
<?php
// Mostra os comentários do mais recente para o mais antigo (array_reverse).
// Para cada comentário, cria um painel com o texto e um formulário para mudar o status.
foreach (array_reverse($filteredComments) as $c):

    // Descobre a obra à qual o comentário pertence
    $w = find_work($c['work_id'] ?? 0);
?>

    <article class="panel" style="margin:12px 0">

        <!-- Cabeçalho: nome do usuário · título da obra · status atual -->
        <div class="eyebrow"><?= e($c['user_name'] ?? 'Usuário') ?> · <?= e($w['title'] ?? 'Obra removida') ?> · <?= e($c['status'] ?? 'published') ?></div>

        <!-- Texto do comentário -->
        <p><?= nl2br(e($c['body'] ?? '')) ?></p>

        <!-- Formulário para o admin mudar o status deste comentário -->
        <form method="post">

            <!-- Token de segurança (CSRF) -->
            <input type="hidden" name="csrf" value="<?= csrf() ?>">

            <!-- ID do comentário que será alterado -->
            <input type="hidden" name="comment_id" value="<?= e($c['id'] ?? 0) ?>">

            <!-- Menu de status disponíveis -->
            <select class="select" name="status">
                <option value="published">Publicado</option>
                <option value="pending">Aguardando análise</option>
                <option value="flagged">Sinalizado</option>
                <option value="removed">Removido</option>
            </select>

            <button class="btn ghost">Atualizar status</button>
        </form>
    </article>

<?php endforeach; ?>

<?php
// Carrega o rodapé do site (scripts, fechamento do HTML)
require __DIR__ . '/../../includes/footer.php';
?>