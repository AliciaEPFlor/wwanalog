<?php
/* WWAnalog — Inicialização da sessão e configuração central da conexão PostgreSQL. */

// ===== INICIALIZAÇÃO DA SESSÃO =====
// Se ainda não existe uma sessão ativa, inicia uma.
// A sessão é o que mantém o usuário "logado" entre páginas.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ===== CONSTANTES DO SITE =====
// Nome curto do site (usado em títulos, logs, etc.).
const SITE_NAME = 'WWAnalog';

// Nome completo/descritivo do site.
const SITE_TITLE = 'Wiki Watch Analog';

/**
 * ===== CONFIGURAÇÃO DE CONEXÃO POSTGRESQL =====
 * Retorna um array com os dados de conexão ao banco.
 * Os dados NÃO secretos (host, porta, nome do banco, usuário) têm valores padrão
 * aqui e podem ser sobrescritos por:
 *   1) variáveis de ambiente (mais seguro para produção);
 *   2) um arquivo local database/config.local.php (não versionado).
 * A SENHA NUNCA fica neste arquivo público — só vem do local.php ou de variável de ambiente.
 */
function database_config(): array
{
    // ===== VALORES PADRÃO =====
    // "getenv(...)" tenta ler variável de ambiente; se não existir, usa o valor após "?:"
    $defaults = [
        'driver'   => 'pgsql',                                          // Tipo de banco
        'host'     => getenv('WWANALOG_DB_HOST') ?: '192.168.10.11',    // Endereço do servidor
        'port'     => getenv('WWANALOG_DB_PORT') ?: '5432',             // Porta (padrão do PostgreSQL)
        'dbname'   => getenv('WWANALOG_DB_NAME') ?: 'wwanalog',         // Nome do banco
        'user'     => getenv('WWANALOG_DB_USER') ?: 'wwadmin',          // Usuário
        'password' => '',                                               // Senha (vazia por padrão — vem do local.php)
    ];

    // ===== ARQUIVO LOCAL DE CONFIGURAÇÃO =====
    // Caminho do arquivo local (fora do controle de versão).
    $localFile = __DIR__ . '/../database/config.local.php';

    // Se o arquivo existe, carrega e mescla com os valores padrão.
    if (is_file($localFile)) {
        $local = require $localFile;
        if (is_array($local)) {
            // array_merge: o que estiver no local.php sobrescreve os padrões.
            $defaults = array_merge($defaults, $local);
        }
    }

    // ===== SENHA POR VARIÁVEL DE AMBIENTE =====
    // Permite configurar a senha pelo ambiente do servidor sem gravá-la no projeto.
    // (Útil em contêineres e ambientes de CI/CD.)
    $envPassword = getenv('WWANALOG_DB_PASSWORD');
    if ($envPassword !== false && $envPassword !== '') {
        $defaults['password'] = $envPassword;
    }

    // Retorna a configuração final.
    return $defaults;
}

/**
 * ===== FUNÇÃO DE CONEXÃO COM O BANCO =====
 * Cria a conexão apenas quando chamada. Não executa DDL nem altera tabelas.
 * Usa "static" para reaproveitar a mesma conexão durante toda a requisição
 * (evita abrir várias conexões desnecessariamente).
 */
function db(): PDO
{
    // Guarda a conexão numa variável estática (persiste entre chamadas).
    static $pdo = null;

    // Se já existe uma conexão, retorna a mesma (economiza recursos).
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Carrega a configuração.
    $cfg = database_config();

    // ===== VALIDAÇÃO: A SENHA FOI CONFIGURADA? =====
    // Se estiver vazia, lança um erro explicativo (mais seguro que tentar conectar sem senha).
    if (($cfg['password'] ?? '') === '') {
        throw new RuntimeException(
            'A senha do PostgreSQL não foi configurada. Preencha database/config.local.php ou defina WWANALOG_DB_PASSWORD.'
        );
    }

    // ===== MONTA O DSN (string de conexão) =====
    // Formato: pgsql:host=...;port=...;dbname=...;connect_timeout=...
    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;connect_timeout=%d',
        $cfg['driver'],
        $cfg['host'],
        $cfg['port'],
        $cfg['dbname'],
        (int) ($cfg['connect_timeout'] ?? 5)   // Timeout de 5s por padrão
    );

    // ===== CRIA A CONEXÃO COM O PDO =====
    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        // Em caso de erro, lança exceção (mais fácil de capturar e tratar).
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // Por padrão, retorna os resultados como arrays associativos (coluna => valor).
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Desativa a emulação de prepared statements:
        // usa os prepared statements NATIVOS do PostgreSQL (mais seguro contra SQL Injection).
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Retorna a conexão pronta para uso.
    return $pdo;
}
?>
