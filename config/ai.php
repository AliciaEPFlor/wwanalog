<?php
/* WWAnalog — Carrega configuração da IA sem embutir credenciais no código público. */
/** Configuração segura do assistente WWAnalog. Não exibe nem registra a chave. */
function wwanalog_ai_api_key(): string {
    $env = getenv('GEMINI_API_KEY');
    if (is_string($env) && trim($env) !== '') return trim($env);
    $localFile = __DIR__ . '/ai.local.php';
    if (is_file($localFile) && is_readable($localFile)) {
        $settings = require $localFile;
        if (is_array($settings)) {
            $key = $settings['api_key'] ?? '';
            if (is_string($key) && trim($key) !== '') return trim($key);
        }
    }
    return '';
}
