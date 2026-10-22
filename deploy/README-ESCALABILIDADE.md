# WWAnalog — implantação escalável (base inicial)

## O que este fluxo implementa

- NGINX na entrada HTTP, publicado em `http://localhost:8080`.
- Quatro processos PHP-FPM (`app1` a `app4`) atrás de um upstream NGINX com `least_conn`.
- Driver `pdo_pgsql` incluído na imagem PHP.
- A aplicação usa o PostgreSQL existente configurado por variáveis de ambiente.
- Nenhum container PostgreSQL é criado e nenhum schema/dado é alterado pelo Compose.

## Iniciar no computador que alcança o PostgreSQL

1. Configure `WWANALOG_DB_PASSWORD` no ambiente do terminal/Compose, sem colocar a senha no Git ou neste arquivo.
2. Confira se o host `192.168.10.11:5432` aceita conexões a partir do computador/container onde o WWAnalog roda. Em Docker Desktop, teste também a conectividade de rede.
3. Na pasta que contém este arquivo `docker-compose.yml`, execute `docker compose up --build -d`.
4. Abra `http://localhost:8080`. Para logs: `docker compose logs -f nginx app1 app2 app3 app4`.
5. Parar sem remover volumes/dados: `docker compose down`. Não use `down -v` para este projeto.

## Atenção: escalabilidade da aplicação ainda exige estado compartilhado

As quatro instâncias compartilham os arquivos do projeto pelo volume local, mas isso não é adequado para múltiplos servidores de produção. Antes de distribuir em máquinas distintas, mova uploads para armazenamento compartilhado e sessões para um armazenamento comum (por exemplo, Redis ou PostgreSQL), e confirme que todo dado de negócio está no banco. O projeto atual ainda contém `database/data.php` com dados em arquivo; o balanceador não migra automaticamente esses dados para PostgreSQL.

## Topologia do banco e réplicas

O banco atual `wwanalog` em `192.168.10.11:5432` é tratado como o endpoint PostgreSQL principal. Este pacote **não** configura réplicas, failover ou um proxy PostgreSQL. Não balanceie gravações de forma round-robin entre primário e réplicas: PostgreSQL exige replicação e eleição/failover configurados corretamente. Uma próxima fase pode introduzir PgBouncer para pooling e Patroni/repmgr + HAProxy ou serviço gerenciado para alta disponibilidade, depois de definir backup, replicação, monitoramento e testes de recuperação.

## Segurança

- Use uma conta de banco dedicada ao aplicativo com privilégios mínimos; evite usar `postgres` em produção.
- Use senha forte e mantenha-a apenas em variável de ambiente/gerenciador de segredos.
- Não exponha a porta 5432 à internet; permita acesso apenas a hosts autorizados.
- HTTPS (443), firewall, backups e observabilidade precisam ser configurados antes de produção.
