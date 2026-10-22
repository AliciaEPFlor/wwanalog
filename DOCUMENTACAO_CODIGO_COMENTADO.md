# WWAnalog — Manual de leitura, comentários e indentação

## Padrão adotado

O código foi revisado para que cada arquivo PHP tenha um comentário inicial que identifique sua responsabilidade. Nos fluxos de maior risco ou complexidade, os comentários também explicam as etapas de validação, autorização, acesso ao banco e apresentação dos dados. O arquivo `.editorconfig` define UTF-8, LF, quatro espaços para indentação e remoção de espaços finais.


## Caminho de uma requisição

1. O servidor abre a página PHP solicitada.
2. `includes/config.php` inicia a sessão e disponibiliza a conexão PostgreSQL por `db()`.
3. `includes/functions.php` oferece funções comuns, como autenticação, verificação de papel, token CSRF, escape HTML e URLs internas.
4. Páginas administrativas chamam `require_admin()` no servidor; esconder um link na interface não substitui essa autorização.
5. Formulários POST validam o token CSRF, validam os campos e usam `PDO::prepare()` com parâmetros.
6. A resposta escapa valores dinâmicos com `htmlspecialchars()`/`e()` para reduzir risco de XSS.

## CRUD PostgreSQL — `people/admin/crud.php`

- **Autorização:** exige administrador no servidor antes de exibir ou processar registros.
- **CSRF:** rejeita POSTs sem token válido.
- **Lista de entidades permitidas:** a entidade vem de uma lista fixa; nomes de tabelas não são aceitos livremente pela URL.
- **Conexão e opções:** carrega categorias, tags, obras, papéis e usuários para preencher listas suspensas.
- **Criar/editar:** valida campos obrigatórios e usa `INSERT` ou `UPDATE` com parâmetros PDO.
- **Relações de obra:** atualiza vínculos em `work_categories` e `work_tags`.
- **Comentários:** cria comentários associados a usuário e obra, ou atualiza conteúdo e status.
- **Usuários:** ao criar, exige senha mínima e grava apenas o hash de `password_hash()`; ao editar, atualiza papel/status sem exibir a senha existente.
- **Excluir:** verifica o ID; obras removem relações dependentes dentro de transação. Exclusão de usuário é bloqueada para não perder dados associados.
- **Auditoria:** registra a operação usando `audit_event()`.
- **Listagem:** as consultas usam parâmetros, ordenação definida no código e limite de registros.

## Filtros PostgreSQL — `people/admin/postgres.php`

- Os parâmetros de busca são lidos de `$_GET` e normalizados como texto ou inteiros.
- Cada entidade tem uma consulta própria e uma lista de colunas permitidas para ordenação.
- Valores de filtro ficam em `$params`, separados do texto SQL; `ILIKE` permite busca textual sem diferenciar maiúsculas/minúsculas.
- O limite e o deslocamento são usados para paginação.
- Os filtros disponíveis dependem das colunas reais, dos tipos e dos valores de status do esquema PostgreSQL instalado.

## Configuração e dados

- `database/config.local.example.php`: exemplo de configuração; copie para `database/config.local.php` e preencha com dados locais.
- `includes/config.php`: carrega a configuração local e permite sobrescrever valores por variáveis de ambiente.
- `database/SEED_INICIAL.sql`: usa `ON CONFLICT DO NOTHING` para que os dados de referência possam ser inseridos novamente sem duplicar as chaves únicas.
- `database/data.php`: armazenamento legado em arrays PHP. As telas antigas que ainda chamam `data_read()`/`data_write()` não são automaticamente migradas para PostgreSQL.
- `config/ai.local.php`: arquivo local de configuração da IA. Não coloque chaves reais em ZIPs públicos ou no controle de versão; use uma chave nova se uma chave já foi exposta.

## Mapa dos diretórios

| Diretório/arquivo | Responsabilidade |
|---|---|
| `index.php` | Página inicial e destaques |
| `catalogo.php` | Busca, filtros e ordenação do catálogo legado |
| `obra.php`, `episodio.php`, `episodios.php` | Detalhes de obras e episódios |
| `login/` | Cadastro, login, perfil e logout |
| `contatos/`, `contatos.php`, `reclamacoes.php` | Contato, correções, reclamações e sugestões |
| `includes/` | Configuração, autenticação, segurança e componentes compartilhados |
| `people/admin/crud.php` | Operações de gravação no PostgreSQL |
| `people/admin/postgres.php` | Consultas e filtros PostgreSQL |
| `people/admin/new.php`, `edit.php`, `episodes.php`, `comments.php` | Módulos administrativos legados que usam `database/data.php` |
| `database/` | Armazenamento legado, configuração de conexão e SQL de carga inicial |
| `analogs/` | Páginas informativas e páginas de episódios de obras individuais |
| `assets/`, `style/` | Imagens, arquivos públicos e estilos |

## Antes de publicar

1. Preencha `database/config.local.php` com credenciais próprias e fortes.
2. Nunca publique senhas, hashes de teste ou chaves de API reais.
3. Execute o SQL inicial apenas depois de criar as tabelas compatíveis com o projeto.
4. Teste em uma base de desenvolvimento: criar, editar, filtrar e excluir registros.
5. Verifique se o usuário do PostgreSQL possui somente as permissões necessárias.
6. O lint de PHP verifica sintaxe, mas não confirma que as consultas funcionarão com o esquema e os dados reais do servidor.
