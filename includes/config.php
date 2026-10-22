<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const SITE_NAME = 'WWAnalog';
const SITE_TITLE = 'Wiki Watch Analog';

/**
 * Configuração de conexão PostgreSQL.
 * Os dados não secretos ficam definidos abaixo; a senha é lida de
 * database/config.local.php, arquivo local que não deve ser publicado.
 */
function database_config(): array
{
    $defaults = [
        'driver' => 'pgsql',
        'host' => getenv('WWANALOG_DB_HOST') ?: '192.168.10.11',
        'port' => getenv('WWANALOG_DB_PORT') ?: '5432',
        'dbname' => getenv('WWANALOG_DB_NAME') ?: 'wwanalog',
        'user' => getenv('WWANALOG_DB_USER') ?: 'postgres',
        'password' => '',
        'connect_timeout' => 5,
    ];

    $localFile = __DIR__ . '/../database/config.local.php';
    if (is_file($localFile)) {
        $local = require $localFile;
        if (is_array($local)) {
            $defaults = array_merge($defaults, $local);
        }
    }

    // Permite configurar a senha por variável de ambiente sem gravá-la no projeto.
    $envPassword = getenv('WWANALOG_DB_PASSWORD');
    if ($envPassword !== false && $envPassword !== '') {
        $defaults['password'] = $envPassword;
    }

    return $defaults;
}

/** Cria a conexão apenas quando chamada; não executa DDL nem altera tabelas. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = database_config();
    if (($cfg['password'] ?? '') === '') {
        throw new RuntimeException(
            'A senha do PostgreSQL não foi configurada. Preencha database/config.local.php ou defina WWANALOG_DB_PASSWORD.'
        );
    }

    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;connect_timeout=%d',
        $cfg['driver'],
        $cfg['host'],
        $cfg['port'],
        $cfg['dbname'],
        (int) ($cfg['connect_timeout'] ?? 5)
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
?>
