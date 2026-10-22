# Conexão PostgreSQL do WWAnalog

## Configuração incluída

- Host: `127.0.0.1` (ou o endereço do seu servidor)
- Porta: `5432`
- Banco existente: `wwanalog`
- Usuário: `wwadmin` (ou o usuário PostgreSQL que você criou)
- Driver PDO: `pgsql` (`pdo_pgsql` precisa estar habilitado no PHP)

## Preparar a senha localmente

1. Copie `database/config.local.example.php` para `database/config.local.php`.
2. Abra `database/config.local.php` no VS Code e informe a senha **atual que já funciona** para o PostgreSQL no campo `password`.
3. Não compartilhe nem publique `config.local.php`. O arquivo está listado no `.gitignore` local desta pasta.
4. Não troque a senha do usuário `postgres` apenas para fazer este teste. Primeiro confirme que a conexão atual funciona.

Como alternativa, configure a variável de ambiente `WWANALOG_DB_PASSWORD` no ambiente em que o PHP é executado.

## Teste sem alterações

Com o servidor PHP iniciado na pasta raiz do projeto, abra:

`http://localhost:8001/database/teste_conexao.php`

Troque a porta da URL se seu servidor usar outra. O teste executa somente `SELECT current_database(), current_user, version()`; não cria, apaga ou altera tabelas. Depois de testar, remova `database/teste_conexao.php`.

## Importante sobre o estado da aplicação

O armazenamento que as páginas atuais utilizam continua sendo `database/data.php` (arquivo PHP local serializado). Esta atualização adiciona a configuração PDO e um teste de conectividade, mas **não migra automaticamente os dados do site para PostgreSQL nem modifica o esquema existente**. Antes de mudar o armazenamento principal, é necessário inspecionar as tabelas que já existem e mapear cada função da aplicação ao esquema real. Nenhuma migração ou instrução destrutiva foi executada.
