<?php
/* WWAnalog — Modelo local para credenciais PostgreSQL; copiar e preencher sem publicar a senha. */
/**
 * Copie para config.local.php e preencha os dados locais do PostgreSQL.
 * Não publique nem compartilhe config.local.php.
 */
return [
    'driver' => 'pgsql',
    'host' => '192.168.10.11',
    'port' => '5432',
    'dbname' => 'wwanalog',
    'user' => 'wwadmin',
    'password' => 'PREENCHA_COM_A_SENHA_DO_POSTGRESQL',
];
