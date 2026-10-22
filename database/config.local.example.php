<?php
// Copie este arquivo como config.local.php e preencha a senha localmente.
// Nunca compartilhe nem publique config.local.php.
return [
    'driver' => 'pgsql',
    'host' => '192.168.10.11',
    'port' => '5432',
    'dbname' => 'wwanalog',
    'user' => 'postgres',
    'password' => 'COLOQUE_A_NOVA_SENHA_AQUI',
    'connect_timeout' => 5,
];
