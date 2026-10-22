<?php
/* WWAnalog — Exibe e atualiza os dados de perfil do usuário autenticado. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../" porque este arquivo está dentro da subpasta login/.
require_once __DIR__ . '/../includes/functions.php';

// Exige que o usuário esteja logado. Se não estiver, manda para a tela de login.
require_login();

// Define o título da página.
$page_title = 'Perfil';

// Nenhuma aba do menu fica destacada nesta página.
$active = '';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/../includes/header.php';

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário.
check_csrf();

// Lê os dados do "estoque" (usuários, listas, obras, etc.).
$d = data_read();

// Pega os dados do usuário atualmente logado.
$current = user();

// Prepara variáveis para mensagens de sucesso e erro.
$msg = '';
$err = '';

// ===== PROCESSAMENTO DO FORMULÁRIO (SÓ SE ENVIOU ALGO) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pega o ID do usuário logado.
    $uid = (int)$current['id'];

    // Percorre TODOS os usuários procurando o que tem esse ID.
    // O "&" antes de $u permite alterar o usuário dentro do laço.
    foreach ($d['users'] as &$u) {

        if ((int)$u['id'] === $uid) {

            // ===== ATUALIZA OS CAMPOS DE TEXTO =====
            // Se o campo não foi enviado, mantém o valor atual.
            $u['name']     = trim($_POST['name'] ?? $u['name']);
            $u['nickname'] = trim($_POST['nickname'] ?? $u['nickname']);
            $u['bio']      = trim($_POST['bio'] ?? ($u['bio'] ?? ''));

            // ===== PROCESSA O UPLOAD DE FOTO DE PERFIL =====
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {

                // Descobre o tipo real do arquivo (não confia só na extensão).
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['avatar']['tmp_name']);

                // Tipos permitidos e a extensão que será usada no nome do arquivo.
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                // Valida: tipo permitido e tamanho máximo de 2 MB.
                if (isset($allowed[$mime]) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {

                    // Pasta onde as fotos ficam: assets/uploads/
                    $dir = __DIR__ . '/../assets/uploads';

                    // Se a pasta não existe, cria.
                    if (!is_dir($dir)) mkdir($dir, 0755, true);

                    // Nome do arquivo: avatar_<id>.<ext>
                    // (usar sempre o mesmo nome faz o arquivo antigo ser substituído)
                    $file = 'avatar_' . $uid . '.' . $allowed[$mime];

                    // Move o arquivo temporário para a pasta definitiva.
                    move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . '/' . $file);

                    // Guarda o caminho relativo no usuário.
                    $u['avatar'] = 'assets/uploads/' . $file;
                } else {
                    $err = 'A foto deve ser JPG, PNG ou WEBP e ter no máximo 2 MB.';
                }
            }

            // Atualiza a variável $current com os dados novos.
            $current = $u;

            // Atualiza também a sessão (para o site inteiro refletir a mudança).
            $_SESSION['user'] = $u;

            break;  // Para de procurar (já achou o usuário).
        }
    }
    unset($u); // Boa prática: desfaz a referência após o laço.

    // Salva as alterações no "estoque".
    data_write($d);

    // Relê os dados atualizados.
    $d = data_read();

    // Se não houve erro no upload, mostra mensagem de sucesso.
    if (!$err) $msg = 'Perfil salvo com sucesso.';
}

// Atualiza $current com os dados mais recentes.
$current = user();

// ===== SEPARA AS OBRAS POR STATUS NA LISTA PESSOAL =====

// Prepara arrays vazios para "já vi", "estou vendo" e "minha lista".
$seen = [];       // Obras marcadas como "Assistido"
$watching = [];   // Obras marcadas como "Assistindo"

// Percorre a lista pessoal de todas as obras.
foreach ($d['lists'] as $l) {

    // Ignora entradas que não são do usuário logado.
    if ((int)($l['user'] ?? 0) !== (int)$current['id']) continue;

    // Busca os dados da obra relacionada.
    $w = find_work($l['work'] ?? 0);

    // Se a obra não existe mais, ignora.
    if (!$w) continue;

    // Separa conforme o status.
    if (($l['status'] ?? '') === 'Assistido')  $seen[] = $w;
    if (($l['status'] ?? '') === 'Assistindo') $watching[] = $w;
}

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho.
ob_start();
?>

<!-- Migalhas de pão -->
<div class="breadcrumbs">WWAnalog / Perfil</div>

<!-- ===== LAYOUT DO PERFIL: CARTÃO (esquerda) + FORMULÁRIO (direita) ===== -->
<div class="profile-layout">

    <!-- ===== CARTÃO DO PERFIL ===== -->
    <section class="profile-card card">

        <!-- Avatar: se tem foto, mostra; se não, mostra a inicial do nome -->
        <div class="profile-avatar">
            <?php if (!empty($current['avatar'])): ?>
                <img src="<?= e(preg_match('~^https?://~i', $current['avatar']) ? $current['avatar'] : url($current['avatar'])) ?>" alt="Foto de perfil">
            <?php else: ?>
                <span><?= e(strtoupper(substr($current['nickname'] ?: $current['name'], 0, 1))) ?></span>
            <?php endif; ?>
        </div>

        <!-- Identidade: papel, apelido/nome e e-mail -->
        <div class="profile-identity">
            <div class="eyebrow">CONTA // <?= e($current['role']) ?></div>
            <h1><?= e($current['nickname'] ?: $current['name']) ?></h1>
            <p class="muted"><?= e($current['email']) ?></p>
        </div>
    </section>

    <!-- ===== FORMULÁRIO DE EDIÇÃO DO PERFIL ===== -->
    <section class="formbox formbox-wide">
        <div class="eyebrow">EDITAR PERFIL</div>
        <h2>Seu espaço</h2>

        <!-- Mensagens de sucesso/erro -->
        <?php if ($msg): ?><div class="notice success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="notice"><?= e($err) ?></div><?php endif; ?>

        <!-- O "enctype=multipart/form-data" é obrigatório para enviar arquivo. -->
        <form method="post" enctype="multipart/form-data">

            <!-- Token de segurança (CSRF) -->
            <input type="hidden" name="csrf" value="<?= csrf() ?>">

            <div class="form-grid">

                <!-- Campo: nome -->
                <div class="field">
                    <label>Nome</label>
                    <input class="input" name="name" value="<?= e($current['name'] ?? '') ?>">
                </div>

                <!-- Campo: apelido -->
                <div class="field">
                    <label>Apelido</label>
                    <input class="input" name="nickname" value="<?= e($current['nickname'] ?? '') ?>">
                </div>

                <!-- Campo: foto de perfil (aceita JPG, PNG ou WEBP) -->
                <div class="field full">
                    <label>Foto de perfil</label>
                    <input class="input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                </div>

                <!-- Campo: descrição/bio (ocupa a linha inteira) -->
                <div class="field full">
                    <label>Descrição / sobre você</label>
                    <textarea class="textarea" name="bio" rows="7" placeholder="Escreva algo sobre você..."><?= e($current['bio'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Botão para salvar -->
            <button class="btn">Salvar perfil</button>
        </form>
    </section>
</div>

<!-- ===== DUAS COLUNAS: "JÁ VI" e "ESTOU VENDO" ===== -->
<div class="profile-lists">

    <!-- Coluna 1: obras marcadas como "Assistido" -->
    <section class="panel">
        <div class="eyebrow">ARQUIVO PESSOAL // 01</div>
        <h2>O que eu já vi</h2>

        <?php if ($seen): ?>
            <div class="mini-list">
                <?php foreach ($seen as $w): ?>
                    <a href="<?= work_path($w) ?>"><?= e($w['title']) ?><span>Assistido</span></a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">Você ainda não marcou nenhuma obra como assistida.</div>
        <?php endif; ?>
    </section>

    <!-- Coluna 2: obras marcadas como "Assistindo" -->
    <section class="panel">
        <div class="eyebrow">ARQUIVO PESSOAL // 02</div>
        <h2>O que estou vendo</h2>

        <?php if ($watching): ?>
            <div class="mini-list">
                <?php foreach ($watching as $w): ?>
                    <a href="<?= work_path($w) ?>"><?= e($w['title']) ?><span>Assistindo</span></a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">Você ainda não marcou nenhuma obra como em andamento.</div>
        <?php endif; ?>
    </section>
</div>

<!-- ===== SEÇÃO: "MINHA LISTA" (obras salvas para ver depois) ===== -->
<section class="panel" style="margin-top:22px">
    <div class="eyebrow">ARQUIVO PESSOAL // 03</div>
    <h2>Minha lista</h2>

    <?php
    // Prepara um array para guardar as obras salvas para ver depois.
    $saved = [];

    // Percorre a lista pessoal e filtra as marcadas como "Adicionado à lista".
    foreach ($d['lists'] as $l) {
        if ((int)($l['user'] ?? 0) === (int)$current['id'] && ($l['status'] ?? '') === 'Adicionado à lista') {
            $x = find_work($l['work'] ?? 0);
            if ($x) $saved[] = $x;
        }
    }

    // Se houver obras salvas, mostra a lista.
    if ($saved): ?>
        <div class="mini-list">
            <?php foreach ($saved as $x): ?>
                <a href="<?= e(work_path($x)) ?>"><?= e($x['title']) ?><span>Adicionado à lista</span></a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty">Sua lista ainda está vazia.</div>
    <?php endif; ?>
</section>

<!-- ===== BOTÕES FINAIS ===== -->
<div class="profile-footer-actions">
    <!-- Voltar ao catálogo -->
    <a class="btn ghost" href="<?= e(url('catalogo.php')) ?>">Voltar ao catálogo</a>

    <!-- Sair da conta -->
    <a class="btn danger" href="<?= e(url('login/logout.php')) ?>">Sair</a>
</div>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content.
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site.
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__ . '/../includes/footer.php';
?>