# Estrutura de dados local do WWAnalog

O projeto usa `database/data.php` como armazenamento local PHP serializado. Para uso público/produção, migrar para MySQL/PostgreSQL com transações, backups e controle de concorrência.

- `categories`: categorias e identificadores.
- `works`: dados da obra, capa, títulos, criador, canal, ano, sinopses, história, tags, situação, classificação, avisos e lista de episódios.
- `works[].episodes[]`: título, duração, URL original, URL de incorporação permitida e descrição.
- `users`: perfil e hash de senha.
- `comments`: comentários com `work_id`; quando `episode_index` existe, o comentário pertence àquele episódio. `status` pode ser `published`, `pending`, `flagged` ou `removed`.
- `ratings`: estrelas de 1 a 5 vinculadas ao usuário e à obra; pode incluir `episode_index`.
- `lists`: estado pessoal por usuário e obra (`Adicionado à lista`, `Assistindo`, `Assistido`).
- `views`: visitas registradas a detalhes/listas de episódios.
- `audit`: ações de autenticação, moderação e administração.
- `chat_logs`: perguntas e respostas do módulo de auxílio, restritas a usuário autenticado.

O painel só apresenta contagens calculadas a partir dos dados armazenados. Ainda não existe gráfico temporal nem processamento de palavras frequentes; não exibir esses indicadores como implementados até que sejam construídos e testados.
