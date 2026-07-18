# Implantação backend em hospedagem compartilhada

1. **Criar banco no cPanel.** Use MySQL Databases e crie um banco vazio para o projeto.
2. **Criar usuário.** Crie um usuário MySQL separado para a aplicação.
3. **Permissões.** Conceda ao usuário somente permissões necessárias no banco do projeto; durante setup, permitir criação/alteração de tabelas para migrations.
4. **Versão PHP.** Selecione PHP 8.1+ no MultiPHP Manager/Selector.
5. **PDO MySQL.** Ative/extensão `pdo_mysql`; valide com `php -m` ou tela de extensões do provedor.
6. **Configuração privada.** Copie `server/config.example.php` para `tna-config.php` fora do web root quando possível e preencha valores reais localmente, sem versionar credenciais.
7. **Upload dos arquivos.** Envie `api/`, `server/`, `database/`, `data/`, `assets/`, `styles/`, scripts necessários e páginas estáticas mantendo caminhos relativos.
8. **Migrations.** Rode `php scripts-build/migrate.php up` via Terminal/SSH do cPanel ou tarefa manual equivalente.
9. **Seeds.** Rode `php scripts-build/seed-database.php apply` para carregar catálogo e snapshot inicial.
10. **Health.** Acesse `/api/v1/health` e confirme `status=ok` e contagens canônicas.
11. **Bootstrap.** Acesse `/api/v1/bootstrap` e valide retorno JSON com catálogo.
12. **HTTPS.** Ative AutoSSL/Let's Encrypt e redirecionamento HTTPS no domínio.
13. **Logs.** Consulte `error_log` do PHP/cPanel; não copie tokens brutos em chamados.
14. **Backup.** Configure backup automático do banco e dos arquivos antes de cada deploy; teste restauração em ambiente separado.
15. **Fallback sem `mod_rewrite`.** Se rewrite estiver indisponível, configure o cliente/staging para chamar `/api/index.php/v1/...` ou ajuste o Apache do host para encaminhar todas as rotas da API ao `index.php`.

Não inclua credenciais neste documento, no Git ou em artefatos públicos.
