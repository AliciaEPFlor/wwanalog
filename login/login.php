<?php
/* WWAnalog — Autentica o usuário, valida a senha e inicia uma sessão segura. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../" porque este arquivo está dentro da subpasta login/.
require_once __DIR__ . '/../includes/functions.php';

// Define o título da página (aparece na aba do navegador).
$page_title = 'Login';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/../includes/header.php';

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário.
check_csrf();

// Prepara uma variável para guardar mensagens de erro.
$err = '';

// ===== PROCESSAMENTO DO FORMULÁRIO (SÓ SE ENVIOU ALGO) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Percorre TODOS os usuários cadastrados no "estoque".
    foreach (data_read()['users'] as $u) {

        // Para cada usuário, verifica DUAS coisas ao mesmo tempo:
        //   1) o e-mail digitado é igual ao e-mail cadastrado? (sem diferenciar maiúsculas)
        //   2) a senha digitada corresponde à senha criptografada guardada? (password_verify)
        if (
            strtolower($u['email']) === strtolower(trim($_POST['email'] ?? '')) &&
            password_verify($_POST['senha'] ?? '', $u['password'])
        ) {
            // Se as duas condições baterem, é o usuário certo!

            // Regenera o ID da sessão (proteção contra "session fixation").
            session_regenerate_id(true);

            // Guarda os dados do usuário na sessão (ele fica "logado").
            $_SESSION['user'] = $u;

            // Registra no histórico de auditoria.
            audit_event('login', 'Login realizado');

            // Redireciona para a home.
            header('Location: ' . url('index.php'));
            exit;
        }
    }

    // Se o laço terminou sem encontrar ninguém, mostra a mensagem de erro.
    // (A mensagem é genérica de propósito: não revela se o e-mail existe ou não.)
    $err = 'E-mail ou senha inválidos.';
}

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho.
ob_start();
?>

<!-- ===== FORMULÁRIO DE LOGIN ===== -->
<div class="formbox">

    <div class="eyebrow">ACESSO AO ARQUIVO</div>
    <h1>Login</h1>

    <!-- Se houve erro (e-mail ou senha inválidos), mostra a mensagem -->
    <?php if ($err): ?>
        <div class="notice"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post">

        <!-- Token de segurança (CSRF) -->
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <!-- Campo: e-mail (obrigatório, tipo email) -->
        <label>E-mail</label>
        <input class="input" type="email" name="email" required>

        <!-- Campo: senha (obrigatório) -->
        <label>Senha</label>
        <input class="input" type="password" name="senha" required>

        <!-- Botão para entrar -->
        <button class="btn">Entrar</button>
    </form>

    <!-- Link para quem ainda não tem conta -->
    <p class="small muted">Ainda não possui conta? <a href="<?= e(url('login/cadastro.php')) ?>">Criar cadastro</a></p>
</div>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content.
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site.
shell($content);

// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__ . '/../includes/footer.php';
?>