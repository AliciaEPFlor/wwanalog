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


## Configuração simplificada da IA Gemini (Windows, XAMPP ou PHP local)

A página do assistente agora aceita duas formas de configuração: variável de ambiente `GEMINI_API_KEY` (preferida em produção) ou arquivo local privado. O arquivo local facilita a instalação no Windows quando o PHP é iniciado pelo XAMPP/Apache e não herda variáveis do terminal.

### Método simples para Windows

1. No Google AI Studio, crie uma chave NOVA. Se uma chave foi enviada em conversa ou compartilhada, revogue-a e não a reutilize.
2. Dentro da pasta do projeto, abra a pasta `config`.
3. Copie `ai.local.php.example` e renomeie a cópia para `ai.local.php`. Os dois arquivos devem ficar na pasta `config`.
4. Abra `config/ai.local.php` no Bloco de Notas ou VS Code. Troque `COLE_SUA_CHAVE_NOVA_AQUI` pela chave nova, mantendo as aspas simples. Não coloque a chave no `ia.php`.
5. Salve o arquivo e reinicie o servidor PHP/Apache.
6. Entre no WWAnalog, abra a página do Assistente de IA e teste: `Explique o que é Analog Horror e cite suas características.`

Exemplo do campo (use sua chave real somente no seu computador):

```php
'api_key' => 'SUA_CHAVE_NOVA_DO_GEMINI',
```

### Método alternativo: terminal

No CMD, antes de iniciar o servidor PHP, execute:

```bat
set GEMINI_API_KEY=SUA_CHAVE_NOVA
php -S 127.0.0.1:8001
```

No PowerShell:

```powershell
$env:GEMINI_API_KEY="SUA_CHAVE_NOVA"
php -S 127.0.0.1:8001
```

A variável precisa estar no mesmo terminal que inicia o `php -S`. Para XAMPP/Apache, prefira o arquivo `config/ai.local.php` ou configure a variável no serviço Apache.

### Requisitos e diagnóstico

- PHP com suporte a HTTPS/TLS e `allow_url_fopen` habilitado, pois a integração usa `file_get_contents` para chamar a API.
- A máquina/servidor precisa ter acesso de saída HTTPS (porta 443) a `generativelanguage.googleapis.com`.
- A chave deve ter acesso à Gemini API e a API/billing/limites do projeto precisam estar em ordem, conforme as regras da conta Google.
- Se aparecer erro de chave recusada, confira se a chave foi copiada completa e se pertence ao Google AI Studio correto.
- Se aparecer limite de solicitações, verifique quotas/limites da API e aguarde ou ajuste o plano.
- Não coloque a chave em JavaScript, HTML, repositório público, captura de tela ou mensagens. Não compartilhe `config/ai.local.php`.
- O arquivo `config/.htaccess` bloqueia acesso HTTP direto em servidores Apache; em Nginx, configure também uma regra de negação para `/config/`.

O assistente envia instruções de sistema e contexto recuperado do catálogo local a cada pergunta. Isso é RAG simples por palavras-chave, não treinamento permanente do modelo. A qualidade depende dos dados cadastrados e do guia em `knowledge/GUIA_ANALOG_HORROR.md`.


## Filtros administrativos conectados ao PostgreSQL

- No painel administrativo, use **Filtros do PostgreSQL** para consultar `works`, `users`, `episodes`, `categories`, `tags`, `comments`, `ratings`, `user_lists`, `contact_requests` e `audit_logs`.
- A tela oferece busca, filtros específicos por módulo, intervalo de datas/anos, ordenação permitida e paginação. As consultas usam PDO e parâmetros preparados.
- Para configurar a conexão, copie `database/config.local.example.php` para `database/config.local.php` e preencha a senha localmente. Não compartilhe esse arquivo.
- As 13 tabelas que você definiu precisam estar criadas no banco `wwanalog` antes de usar essa tela.
- **Importante:** as telas antigas do site continuam usando `database/data.php`. A página nova consulta o PostgreSQL, mas não migra automaticamente dados nem troca as operações de cadastro/edição existentes para o PostgreSQL. Essa migração deve ser feita módulo por módulo para evitar inconsistência.
- A tela de PostgreSQL é de consulta; não executa cadastro, edição ou exclusão das tabelas relacionais.

## CRUD administrativo conectado ao PostgreSQL

Acesse `people/admin/crud.php` com uma sessão administrativa para cadastrar, pesquisar e editar obras, categorias, tags e episódios diretamente no PostgreSQL. Também permite moderar comentários, atualizar status/papel de usuários e atualizar ou excluir solicitações de contato. As consultas usam PDO e parâmetros preparados; as ações POST exigem CSRF e a página exige administrador no servidor.

`people/admin/postgres.php` é a tela de consulta/filtros SQL (SELECT), com pesquisa, filtros e paginação. Ela não é a tela de edição. As telas administrativas antigas (`new.php`, `edit.php`, `episodes.php`, `comments.php` e o painel antigo) ainda usam `database/data.php`; para alterações persistidas no PostgreSQL, utilize `crud.php`. O CRUD não apaga usuários e não altera senhas para preservar vínculos e reduzir risco de perda de dados.

Antes de usar, configure `database/config.local.php` a partir de `database/config.local.example.php`, confirme que as 13 tabelas foram criadas e execute `database/SEED_INICIAL.sql` para inserir papéis, categorias, tags e obras iniciais.


## Código comentado e padrão de indentação

Consulte `DOCUMENTACAO_CODIGO_COMENTADO.md` para entender a responsabilidade de cada módulo, o fluxo de autenticação, o CRUD PostgreSQL e os filtros SQL. O arquivo `.editorconfig` define o padrão de indentação para novas alterações. Os comentários explicam arquivos e blocos lógicos; não se comenta cada linha vazia, chave ou tag individualmente porque isso torna templates PHP/HTML mais difíceis de manter.
# wwanalog
