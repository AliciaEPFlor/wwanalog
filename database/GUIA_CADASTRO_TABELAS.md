# Guia de preenchimento das 13 tabelas do WWAnalog

## Ordem de cadastro
1. `roles`: `name`, `description`; o `id` é automático.
2. `categories`: `name`, `slug`, `description`; IDs e datas são automáticos.
3. `tags`: `name`, `slug`; ID automático.
4. `works`: `title` obrigatório; `slug`, descrição, capa, criador, ano, situação, classificação, avisos e fonte quando confirmados. Deixe desconhecidos como `NULL`.
5. `work_categories`: pares `work_id`, `category_id` existentes.
6. `work_tags`: pares `work_id`, `tag_id` existentes.
7. `users`: nome, apelido, e-mail, hash de senha, avatar/bio opcionais, `role_id` e status. Use `password_hash($senha, PASSWORD_DEFAULT)` em PHP; nunca grave senha pura.
8. `episodes`: `work_id`, título e demais dados confirmados; número/data/links podem ficar nulos.
9. `ratings`: criado pelo site; `user_id`, `work_id`, `score` de 1 a 5.
10. `comments`: criado pelo site; `user_id`, `work_id`, conteúdo e status.
11. `user_lists`: criado pelo site; `user_id`, `work_id`, status da lista.
12. `contact_requests`: criado pelo formulário; tipo, assunto, mensagem e dados de contato disponíveis.
13. `audit_logs`: gerado pelo backend em ações importantes; não preencher manualmente no uso normal.

## Valores recomendados
- `roles.name`: `administrador`, `moderador`, `membro`. Visitantes anônimos não precisam ter conta.
- `users.status`: `ativo`, `suspenso`, `inativo`.
- `works.status`: mantenha valores consistentes, por exemplo `Em andamento`, `Concluída`, `Cancelada`; o esquema atual não impõe um CHECK para esse campo.
- `comments.status`: `publicado`, `oculto`, `removido`.
- `user_lists.list_status`: `minha_lista`, `assistindo`, `assistido`.
- `contact_requests.status`: `pendente`, `em_analise`, `resolvida`, `rejeitada`.
- `contact_requests.request_type`: use os valores controlados pela aplicação: `correcao`, `atualizacao`, `remocao`, `reclamacao`, `sugestao`, `outro`.

## Obras iniciais
O arquivo `database/SEED_INICIAL.sql` inclui papéis, categorias, tags, 13 obras e seus vínculos. Não inclui usuários, episódios, avaliações, comentários, listas, solicitações nem auditoria: essas linhas dependem de dados reais de usuários/episódios e devem ser geradas pelo site ou inseridas quando confirmadas.
