<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: text/html; charset=UTF-8');

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = db();
    // Consulta exclusivamente de leitura: não cria, apaga ou altera tabelas.
    $info = $pdo->query(
        'SELECT current_database() AS banco, current_user AS usuario, version() AS versao'
    )->fetch();
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Teste PostgreSQL — WWAnalog</title>
      <style>body{font:16px system-ui,sans-serif;max-width:760px;margin:4rem auto;padding:0 1rem;background:#111;color:#eee}main{border:1px solid #555;border-radius:10px;padding:1.5rem}h1{color:#b7d7b0}.label{color:#aaa}.value{overflow-wrap:anywhere}</style>
    </head><body><main>
      <h1>Conexão PostgreSQL estabelecida</h1>
      <p>O WWAnalog conseguiu consultar o servidor. Este teste não modificou o banco.</p>
      <p><span class="label">Banco:</span> <strong><?= h($info['banco'] ?? '') ?></strong></p>
      <p><span class="label">Usuário conectado:</span> <strong><?= h($info['usuario'] ?? '') ?></strong></p>
      <p><span class="label">Servidor:</span> <span class="value"><?= h($info['versao'] ?? '') ?></span></p>
      <p>Por segurança, apague este arquivo de teste depois de confirmar a conexão.</p>
    </main></body></html>
    <?php
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[WWAnalog PostgreSQL] ' . $e->getMessage());
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Teste PostgreSQL</title>';
    echo '<body style="font:16px system-ui;max-width:700px;margin:4rem auto;padding:0 1rem">';
    echo '<h1>Não foi possível conectar</h1>';
    echo '<p>Confira se a senha atual está configurada em <code>database/config.local.php</code>, se o servidor está acessível e se o usuário tem permissão de conexão.</p>';
    echo '<p>Detalhes técnicos foram gravados no log do PHP, sem exibir credenciais nesta página.</p></body></html>';
}
