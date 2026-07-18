# Troubleshooting

- Erro 500/página branca: verifique versão PHP, `error_log`, caminho do bootstrap privado e `debug=false`.
- PHP incompatível: selecione PHP 8.1+.
- PDO ausente: habilite `pdo_mysql` no seletor de extensões.
- Conexão MySQL: confira host, banco, usuário, senha e privilégios na configuração privada.
- Migration pendente/seed incompleto: execute `migrate.php verify` e `seed-database.php verify`.
- `.htaccess` ignorado/rewrite indisponível: teste `/api/index.php?route=/v1/health` e ajuste o domínio para processar `.htaccess`.
- CORS: use apenas origens esperadas; não deixe `*` permanente.
- HTTPS em loop: desative redirecionamento forçado até confirmar proxy/`X-Forwarded-Proto`.
- Angular não inicia/manifesto ausente/404 JS: verifique `assets/story-engine/story-engine-assets.json` e bundles com hash.
- Caminhos relativos/fallback não aparece: confirme que o host Angular está dentro de `#visual-system` e que arquivos `data/` existem.
- Token inválido/conflito 409: recrie sessão ou reconcilie revisão; não registre token bruto.
- Cache antigo: limpe cache e confirme headers; bundles com hash podem usar cache longo.
- Permissões/limite de memória/upload: ajuste no cPanel sem relaxar segurança permanentemente.
- Logs: mantenha fora de `public_html` e revise sem expor credenciais.
