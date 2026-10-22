<?php
/* WWAnalog — Carrega configuração da IA sem embutir credenciais no código público. */

/**
 * ===== CONFIGURAÇÃO SEGURA DO ASSISTENTE WWANALOG =====
 * Esta função retorna a chave de API do Gemini (a "senha" que libera o uso da IA).
 * A chave NUNCA fica escrita neste arquivo público — ela vem de duas fontes seguras:
 *
 *   1) Variável de ambiente GEMINI_API_KEY (mais seguro, ideal para produção);
 *   2) Arquivo local config/ai.local.php (não versionado, útil em desenvolvimento).
 *
 * A função NÃO exibe nem registra a chave em nenhum lugar.
 * Retorna string vazia se não encontrar nenhuma chave configurada.
 */
function wwanalog_ai_api_key(): string {

    // ===== FONTE 1: VARIÁVEL DE AMBIENTE =====
    // Tenta ler a variável GEMINI_API_KEY do ambiente do servidor.
    $env = getenv('GEMINI_API_KEY');

    // Se a variável existe, é string e não está vazia (só com espaços)...
    if (is_string($env) && trim($env) !== '') {
        // Retorna a chave já sem espaços extras.
        return trim($env);
    }

    // ===== FONTE 2: ARQUIVO LOCAL DE CONFIGURAÇÃO =====
    // Caminho do arquivo local (mesma pasta deste arquivo).
    // Esse arquivo NÃO deve ser publicado no repositório — fica só no servidor.
    $localFile = __DIR__ . '/ai.local.php';

    // Só tenta carregar se o arquivo existe e é legível.
    if (is_file($localFile) && is_readable($localFile)) {

        // Carrega o conteúdo do arquivo (deve retornar um array com a chave).
        $settings = require $localFile;

        // Se o retorno for um array válido...
        if (is_array($settings)) {

            // Pega o valor da chave 'api_key'. Se não existir, usa string vazia.
            $key = $settings['api_key'] ?? '';

            // Se a chave é string e não está vazia...
            if (is_string($key) && trim($key) !== '') {
                // Retorna a chave já sem espaços extras.
                return trim($key);
            }
        }
    }

    // ===== NENHUMA CHAVE ENCONTRADA =====
    // Retorna string vazia — quem chamou deve tratar isso (ex.: mostrar erro amigável).
    return '';
}