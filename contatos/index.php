<?php
/* WWAnalog — Formulário/área de contato: recebe mensagens e solicitações dos visitantes. */

// Carrega as funções auxiliares do sistema (helpers, validações, etc.).
// O caminho tem "../" porque este arquivo está dentro da subpasta contatos/.
require_once __DIR__.'/../includes/functions.php';

// Define o título da página e marca "contact" como aba ativa do menu.
$page_title = 'Contatos';
$active = 'contact';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__.'/../includes/header.php';

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho.
ob_start();
?>

<!-- Migalhas de pão: caminho de navegação (WWAnalog / Contatos) -->
<div class="breadcrumbs">WWAnalog / Contatos</div>

<!-- ===== CABEÇALHO DA SEÇÃO ===== -->
<div class="section-head">
    <div>
        <h1>Contatos</h1>
        <small>Canais oficiais e contato da equipe</small>
    </div>
</div>

<!-- ===== DUAS COLUNAS: CANAIS DA COMUNIDADE e CONTATOS DO DEV ===== -->
<div class="contact-grid">

    <!-- ===== COLUNA 1: CANAIS DA COMUNIDADE ===== -->
    <section class="panel contact-box">
        <div class="eyebrow">CANAIS DA COMUNIDADE</div>
        <h2>Acesse nossos canais</h2>

        <!-- Lista de canais: cada item é um par "rótulo : valor" -->
        <div class="contact-list">

            <!-- Fórum no Discord (link ainda pendente) -->
            <div>
                <strong>Acesse nosso fórum no Discord:</strong>
                <span class="pending-link">link do fórum</span>
            </div>

            <!-- Grupo no Telegram (link ainda pendente) -->
            <div>
                <strong>Acesse nosso grupo no Telegram:</strong>
                <span class="pending-link">link do grupo</span>
            </div>

            <!-- Grupo no WhatsApp (link ainda pendente) -->
            <div>
                <strong>Acesse nosso grupo no WhatsApp:</strong>
                <span class="pending-link">link do grupo</span>
            </div>

            <!-- Canal do desenvolvedor (link ainda pendente) -->
            <div>
                <strong>Acessar o canal do dev:</strong>
                <span class="pending-link">link do canal do dev</span>
            </div>
        </div>
    </section>

    <!-- ===== COLUNA 2: CONTATOS DO DESENVOLVEDOR ===== -->
    <section class="panel contact-box">
        <div class="eyebrow">EQUIPE DE DESENVOLVIMENTO</div>
        <h2>Contatos de dev</h2>

        <div class="contact-list">

            <!-- E-mail: link mailto que abre o cliente de e-mail -->
            <div>
                <strong>Email</strong>
                <a href="mailto:aliciaelizabethpilarflor@gmail.com">aliciaelizabethpilarflor@gmail.com</a>
            </div>

            <!-- Discord: apenas texto (não é link clicável) -->
            <div>
                <strong>Discord</strong>
                <span>_*oleandro.*_verde_</span>
            </div>

            <!-- Instagram: apenas texto (não é link clicável) -->
            <div>
                <strong>Instagram Oficial</strong>
                <span>_oleandro._verde_</span>
            </div>

            <!-- LinkedIn: link externo que abre em nova aba.
                 O "rel=noopener" evita que a página aberta tenha acesso à página original. -->
            <div>
                <strong>LinkedIn</strong>
                <a href="https://www.linkedin.com/in/alicia-elizabeth-pilar-flor/"
                   target="_blank" rel="noopener">alicia-elizabeth-pilar-flor</a>
            </div>

            <!-- GitHub: link externo que abre em nova aba. -->
            <div>
                <strong>GitHub</strong>
                <a href="https://github.com/AliciaEPFlor"
                   target="_blank" rel="noopener">AliciaEPFlor</a>
            </div>
        </div>
    </section>
</div>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content.
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site (com trilho lateral).
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__.'/../includes/footer.php';
?>
