<?php
/* WWAnalog — Formulário para registrar reclamações, correções, sugestões e solicitações. */

// Define o título da página (aparece na aba do navegador)
$page_title = 'Reclame Aqui';

// Diz ao menu qual aba deve ficar destacada (a aba "complaint")
$active = 'complaint';

// Carrega o topo do site (logo, menu, etc.)
require __DIR__ . '/includes/header.php';

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário
check_csrf();

// Prepara uma variável para guardar a mensagem de sucesso
$msg = '';

// ===== SÓ ENTRA AQUI SE O USUÁRIO ENVIOU UM FORMULÁRIO (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lê os dados do "estoque"
    $d = data_read();

    // Pega o tipo da mensagem (Reclamação ou Sugestão).
    // Se não vier nada, assume "Reclamação" como padrão.
    $type = trim($_POST['type'] ?? 'Reclamação');

    // Adiciona uma nova entrada na lista de contatos:
    //  - ID (quantidade atual + 1)
    //  - tipo (Reclamação ou Sugestão)
    //  - mensagem digitada
    //  - usuário que enviou (se estiver logado; senão fica vazio)
    //  - status inicial "Aberto"
    $d['contacts'][] = [
        'id'      => count($d['contacts']) + 1,
        'type'    => $type,
        'message' => trim($_POST['message'] ?? ''),
        'user'    => user()['id'] ?? null,
        'status'  => 'Aberto'
    ];

    // Salva no "estoque"
    data_write($d);

    // Define a mensagem de sucesso dependendo do tipo enviado
    $msg = $type === 'Sugestão'
        ? 'Sugestão enviada com sucesso.'
        : 'Reclamação enviada com sucesso.';
}

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho
ob_start();
?>

<!-- Migalhas de pão: mostra o caminho (WWAnalog / Reclame Aqui) -->
<div class="breadcrumbs">WWAnalog / Reclame Aqui</div>

<!-- ===== BANNER DE APRESENTAÇÃO (versão compacta) ===== -->
<div class="hero compact-hero">
    <div class="eyebrow">CANAL DE ATENDIMENTO</div>
    <h1>Reclame Aqui</h1>
    <p>Use este espaço para enviar uma reclamação ou compartilhar uma sugestão com a equipe da WWAnalog.</p>
</div>

<!-- Se houve envio bem-sucedido, mostra a mensagem de sucesso -->
<?php if ($msg): ?>
    <div class="notice"><?= e($msg) ?></div>
<?php endif; ?>

<!-- ===== DUAS COLUNAS: RECLAMAÇÃO E SUGESTÃO ===== -->
<div class="two-col complaint-grid">

    <!-- Coluna 1: formulário de RECLAMAÇÃO -->
    <section class="formbox">
        <div class="eyebrow">01 // RECLAMAÇÃO</div>
        <h2>Escreva sua mensagem</h2>

        <form method="post">
            <!-- Token de segurança (CSRF) -->
            <input type="hidden" name="csrf" value="<?= csrf() ?>">

            <!-- Campo escondido que diz ao sistema: "isto é uma reclamação" -->
            <input type="hidden" name="type" value="Reclamação">

            <label>Mensagem</label>
            <textarea class="textarea" name="message" rows="9" required placeholder="Descreva sua reclamação...">
            </textarea>

            <button class="btn">Enviar reclamação</button>
        </form>
    </section>

    <!-- Coluna 2: formulário de SUGESTÃO -->
    <section class="formbox">
        <div class="eyebrow">02 // SUGESTÃO</div>
        <h2>Faça uma sugestão</h2>

        <form method="post">
            <!-- Token de segurança (CSRF) -->
            <input type="hidden" name="csrf" value="<?= csrf() ?>">

            <!-- Campo escondido que diz ao sistema: "isto é uma sugestão" -->
            <input type="hidden" name="type" value="Sugestão">

            <label>Sugestão</label>
            <textarea class="textarea" name="message" rows="9" required placeholder="Escreva sua sugestão..."></textarea>

            <button class="btn">Enviar sugestão</button>
        </form>
    </section>
</div>

<!-- Aviso em destaque sobre remoção/correção de conteúdo -->
<div class="removal-alert">CASO DESEJE A REMOÇÃO DE UM CONTEÚDO, ATUALIZAÇÃO OU CORREÇÃO, ENTRE EM CONTATO COM NOSSA EQUIPE.</div>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML)
require __DIR__ . '/includes/footer.php';
?>