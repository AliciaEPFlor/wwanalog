<?php
/* WWAnalog — Valida e cadastra uma nova conta de usuário. */
require_once __DIR__ . '/../includes/functions.php';
$page_title = 'Cadastro';
require __DIR__ . '/../includes/header.php';
check_csrf();
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = data_read();
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $name = trim($_POST['nome'] ?? '');
    $nick = trim($_POST['apelido'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 8 || $name === '') $err = 'Preencha os campos corretamente. A senha deve ter pelo menos 8 caracteres.';
    elseif (array_filter($d['users'], fn($u) => strtolower($u['email']) === strtolower($email))) $err = 'Este e-mail já está cadastrado.';
    else {
        $u = ['id' => max(array_column($d['users'], 'id') ?: [0]) + 1, 'name' => $name, 'nickname' => $nick, 'email' => $email, 'password' => password_hash($senha, PASSWORD_DEFAULT), 'role' => 'Usuário', 'bio' => '', 'created_at' => date('c')];
        $d['users'][] = $u;
        data_write($d);
        session_regenerate_id(true);
        $_SESSION['user'] = $u;
        audit_event('cadastro', 'Nova conta criada');
        header('Location: ' . url('index.php?cadastro=sucesso'));
        exit;
    }
}
ob_start(); ?><div class="formbox">
    <div class="eyebrow">NOVO REGISTRO</div>
    <h1>Criar conta</h1><?php if ($err): ?><div class="notice"><?= e($err) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Nome</label><input class="input" name="nome" required><label>Apelido</label><input class="input" name="apelido"><label>E-mail</label><input class="input" type="email" name="email" required><label>Senha</label><input class="input" type="password" name="senha" minlength="8" required><button class="btn">Cadastrar e entrar</button></form>
    <p class="small muted"><a href="<?= e(url('login/login.php')) ?>">Voltar para login</a></p>
</div><?php $content = ob_get_clean();
        shell($content);
        require __DIR__ . '/../includes/footer.php'; ?>