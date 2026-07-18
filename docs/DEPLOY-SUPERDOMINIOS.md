# Deploy SuperDomínios/cPanel — The Ninth Art

## 1. Requisitos
- Apache com `.htaccess` e, idealmente, `mod_rewrite`, `mod_headers`, `mod_mime` e `mod_deflate`.
- PHP 8.1+ com `pdo`, `pdo_mysql`, `mbstring` e `json`.
- MySQL ou MariaDB vazio para a primeira publicação.
- Node é usado somente no build local; não configure aplicativo Node em produção.

## 2. Backup
Antes de trocar arquivos, baixe cópia completa do `public_html` atual e exporte o banco existente, se houver.

## 3. Acesso ao cPanel
Acesse o painel da hospedagem e use as áreas equivalentes para arquivos, PHP, banco MySQL e SSL. Os nomes podem variar entre temas do cPanel.

## 4. Seleção do PHP
Selecione PHP 8.1 ou superior para o domínio e confirme `pdo_mysql`, `mbstring` e `json`.

## 5. Criação do MySQL
Crie um banco, crie um usuário, associe o usuário ao banco e conceda privilégios necessários para criar/alterar tabelas durante o setup. Registre host, nome do banco e usuário apenas na configuração privada; nunca publique a senha.

## 6. Estrutura privada
Preferencialmente use:

```text
/home/USUARIO/
  private/theninthart/
    server/
    config.php
    logs/
  public_html/
    api/
    assets/
    index.html
```

Copie `release/private/config.example.php` para um arquivo privado real e preencha localmente. Se necessário, defina `TNA_SERVER_BOOTSTRAP_PATH` para o caminho absoluto de `server/bootstrap.php` e `TNA_CONFIG_PATH` para a configuração privada.

## 7. Upload
Use Gerenciador de Arquivos do cPanel ou FTP/SFTP. Prefira enviar ZIP para diretório temporário, extrair, verificar checksums e só então trocar pastas. Remova ZIPs temporários após a ativação.

## 8. Configuração
Configure `environment=production`, `debug=false`, URL base, origens permitidas, segredo forte, diretório de logs privado e credenciais MySQL. Permissões recomendadas: diretórios `0755`, arquivos `0644` e configuração privada com a menor permissão aceita pelo host.

## 9. Migrations e seeds
### Estratégia A — Terminal do cPanel disponível
```sh
php scripts-build/migrate.php status
php scripts-build/migrate.php dry-run
php scripts-build/migrate.php up
php scripts-build/migrate.php verify
php scripts-build/seed-database.php check
php scripts-build/seed-database.php apply
php scripts-build/seed-database.php verify
```

### Estratégia B — Terminal indisponível
Não crie endpoint público de instalação. Alternativas: executar scripts CLI localmente contra o MySQL remoto quando permitido, importar SQL revisado pelo phpMyAdmin, ou usar script CLI temporário fora de `public_html` e removê-lo após o uso.

## 10. HTTPS e `.htaccess`
Ative SSL/AutoSSL no painel. O `.htaccess` inclui proteção de diretórios, cache por tipo, MIME e compressão condicionais. Redirecionamento HTTPS forçado só deve ser ativado quando o host/proxy estiver validado para evitar loop.

## 11. Health e testes
Teste `/api/v1/health`, `/api/v1/bootstrap` e o fallback sem rewrite `/api/index.php?route=/v1/health`. Execute a checklist e `POST-DEPLOY-TESTS.md`.

## 12. Ativação e rollback
Ative por troca controlada de diretórios; se symlink não for permitido, renomeie a versão atual para `previous` e mova a nova para `public_html`. Mantenha backup para rollback e não reverta migrations destrutivamente sem restauração consciente do banco.

## 13. Segurança pós-deploy
Confirme que `.env`, `server/`, `database/`, logs, backups, `frontend/`, `tests/` e `scripts-build/` não são públicos. Verifique erros 404/403, logs sem tokens, CORS restrito e cache `no-store` em endpoints privados.

## 14. Resolução de problemas
Consulte `TROUBLESHOOTING.md` para erro 500, PHP incompatível, PDO ausente, conexão MySQL, rewrite, HTTPS em loop, Angular, cache e permissões.
