<?php
/* WWAnalog — Modelo local para credenciais PostgreSQL; copiar e preencher sem publicar a senha. */
/**
 * Copie para config.local.php e preencha os dados locais do PostgreSQL.
 * Não publique nem compartilhe config.local.php.
 */
return [
    'driver' => 'pgsql',
    'host' => '127.0.0.1',
    'port' => '5432',
    'dbname' => 'wwanalog',
    'user' => 'wwadmin',
    'password' => '12345',
];
