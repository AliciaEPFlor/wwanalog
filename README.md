# WWAnalog — PHP + PostgreSQL (sem Docker)

## Configurar a conexão

1. Abra a pasta `wwanalog_fixed` no VS Code.
2. Copie `database/config.local.example.php` para `database/config.local.php`.
3. Abra `database/config.local.php` e substitua `COLOQUE_A_NOVA_SENHA_AQUI` pela senha atual do PostgreSQL. Não compartilhe esse arquivo.
4. O arquivo `includes/config.php` já está preparado para usar PDO PostgreSQL com estes valores padrão:
   - Host: `192.168.10.11`
   - Porta: `5432`
   - Banco: `wwanalog`
   - Usuário: `postgres`
5. Execute o projeto usando seu servidor PHP habitual. O servidor PHP precisa ter a extensão `pdo_pgsql` habilitada e conseguir alcançar `192.168.10.11:5432`.
6. Abra `/database/teste_conexao.php` no navegador para testar. A página executa apenas um SELECT de leitura; não cria, apaga nem altera tabelas.
7. Depois de confirmar a conexão, apague `database/teste_conexao.php`.

## Importante sobre o estado atual

O teste confirma que o PHP consegue abrir uma conexão PDO com PostgreSQL. Entretanto, as páginas principais ainda leem e gravam conteúdo por meio de `database/data.php`. Portanto, configurar a conexão não migra automaticamente obras, usuários, episódios, comentários, avaliações ou listas para o PostgreSQL. A integração dessas funcionalidades deve ser feita depois de inspecionar o esquema existente e sem executar comandos destrutivos.

Não há Dockerfile nem Docker Compose neste pacote.


## Atualização das páginas de Analog Horror

- As páginas individuais compartilham `includes/analog_detail.php`.
- Capas menores e responsivas, botão “Clique aqui para assistir!” e metadados da obra.
- Removida a caixa “Arquivo” das páginas individuais.
- As páginas de episódios apontam para a lista central e o player/comentários por episódio continuam em `episodio.php`.
- Campos não cadastrados continuam como “Não informado”; não inventar metadados.

## Configurar o assistente de IA (Gemini)

A página `ia.php` usa a API Gemini e lê a chave somente da variável de ambiente `GEMINI_API_KEY`. Não cole a chave diretamente no PHP, em arquivos públicos ou no repositório.

### Linux/macOS (temporário, no terminal que inicia o servidor)

```bash
export GEMINI_API_KEY="COLOQUE_SUA_CHAVE_NOVA_AQUI"
php -S 127.0.0.1:8001
```

### Windows PowerShell (na mesma sessão que inicia o PHP)

```powershell
$env:GEMINI_API_KEY="COLOQUE_SUA_CHAVE_NOVA_AQUI"
php -S 127.0.0.1:8001
```

Em Apache/Nginx/PHP-FPM ou hospedagem, configure `GEMINI_API_KEY` nas variáveis de ambiente do serviço e reinicie o serviço PHP. O método exato depende do servidor. A chave fornecida em conversa deve ser revogada e substituída por uma nova, pois já foi exposta. A integração usa o endpoint Gemini `gemini-2.5-flash`; exige saída HTTPS do servidor para `generativelanguage.googleapis.com`. Não exige Composer nem `vendor/autoload.php`.

## Agente WWAnalog especializado em Analog Horror
A página `ia.php` envia instruções de sistema especializadas e recupera contexto relevante do catálogo local em `database/data.php` para cada pergunta (RAG simples por correspondência de palavras-chave). Assim, títulos, descrições e episódios cadastrados são enviados ao Gemini quando relacionados à pergunta. O guia editorial está em `knowledge/GUIA_ANALOG_HORROR.md`.

Isso não é treinamento permanente nem fine-tuning do modelo Gemini: é instrução + recuperação de contexto em tempo de consulta. Para enriquecer o agente, mantenha descrições e episódios corretos no catálogo e amplie o guia com material autoral/verificado. Não coloque chaves de API em arquivos públicos; use `GEMINI_API_KEY` no ambiente do servidor.
