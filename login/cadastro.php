<?php
/* WWAnalog — Valida e cadastra uma nova conta de usuário. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../" porque este arquivo está dentro da subpasta login/.
require_once __DIR__ . '/../includes/functions.php';

// Define o título da página (aparece na aba do navegador).
$page_title = 'Cadastro';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/../includes/header.php';

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário.
check_csrf();

// Prepara variáveis para guardar mensagens de sucesso e erro.
$msg = '';
$err = '';

// ===== PROCESSAMENTO DO FORMULÁRIO (SÓ SE ENVIOU ALGO) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lê os dados do "estoque" (onde ficam os usuários cadastrados).
    $d = data_read();

    // Pega os campos do formulário, tirando espaços extras.
    $email = trim($_POST['email'] ?? '');       // E-mail
    $senha = $_POST['senha'] ?? '';             // Senha (NÃO usa trim para não cortar espaços significativos)
    $name  = trim($_POST['nome'] ?? '');        // Nome completo
    $nick  = trim($_POST['apelido'] ?? '');     // Apelido (opcional)

    // ===== VALIDAÇÕES =====

    // Validação 1: e-mail válido, senha com 8+ caracteres e nome obrigatório.
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 8 || $name === '') {
        $err = 'Preencha os campos corretamente. A senha deve ter pelo menos 8 caracteres.';

    // Validação 2: verifica se o e-mail já está cadastrado (comparação sem diferenciar maiúsculas).
    } elseif (array_filter($d['users'], fn($u) => strtolower($u['email']) === strtolower($email))) {
        $err = 'Este e-mail já está cadastrado.';

    // Se passou em todas as validações, cria a conta.
    } else {

        // Monta o novo usuário:
        //  - novo ID (maior existente + 1)
        //  - nome, apelido, e-mail
        //  - senha CRIPTOGRAFADA com password_hash (nunca guardamos a senha "pura")
        //  - papel inicial "Usuário"
        //  - bio vazia
        //  - data/hora de criação
        $u = [
            'id'         => max(array_column($d['users'], 'id') ?: [0]) + 1,
            'name'       => $name,
            'nickname'   => $nick,
            'email'      => $email,
            'password'   => password_hash($senha, PASSWORD_DEFAULT),
            'role'       => 'Usuário',
            'bio'        => '',
            'created_at' => date('c')
        ];

        // Adiciona o novo usuário na lista de usuários do "estoque".
        $d['users'][] = $u;

        // Salva no "estoque".
        data_write($d);

        // Regenera o ID da sessão (proteção contra "session fixation").
        session_regenerate_id(true);

        // Faz o login automático do usuário recém-cadastrado.
        $_SESSION['user'] = $u;

        // Registra no histórico de auditoria.
        audit_event('cadastro', 'Nova conta criada');

        // Redireciona para a home com o parâmetro "cadastro=sucesso".
        header('Location: ' . url('index.php?cadastro=sucesso'));
        exit;
    }
}

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho.
ob_start();
?>

<!-- ===== FORMULÁRIO DE CADASTRO ===== -->
<div class="formbox">

    <div class="eyebrow">NOVO REGISTRO</div>
    <h1>Criar conta</h1>

    <!-- Se houve algum erro de validação, mostra a mensagem -->
    <?php if ($err): ?>
        <div class="notice"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post">

        <!-- Token de segurança (CSRF) -->
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <!-- Campo: nome completo (obrigatório) -->
        <label>Nome</label>
        <input class="input" name="nome" required>

        <!-- Campo: apelido (opcional) -->
        <label>Apelido</label>
        <input class="input" name="apelido">

        <!-- Campo: e-mail (obrigatório, tipo email) -->
        <label>E-mail</label>
        <input class="input" type="email" name="email" required>

        <!-- Campo: senha (obrigatório, mínimo 8 caracteres) -->
        <label>Senha</label>
        <input class="input" type="password" name="senha" minlength="8" required>

        <!-- Botão para cadastrar e já entrar no site -->
        <button class="btn">Cadastrar e entrar</button>
    </form>

    <!-- Link para voltar à tela de login -->
    <p class="small muted"><a href="<?= e(url('login/login.php')) ?>">Voltar para login</a></p>
</div>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content.
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site.
shell($content);

// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__ . '/../includes/footer.php';
?>