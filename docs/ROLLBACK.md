# Rollback

1. Não apague backups anteriores.
2. Coloque a aplicação em troca controlada temporária, se necessário, sem deixar `maintenance.flag` ativo no pacote final.
3. Restaure os arquivos anteriores de `public_html` ou renomeie `previous/` de volta.
4. Restaure a configuração privada anterior se ela foi alterada.
5. Para banco, use backup anterior obrigatório quando migration incompatível tiver sido aplicada; não execute rollback automático destrutivo.
6. Confirme checksums da versão restaurada quando disponíveis.
7. Limpe cache do navegador/CDN/hospedagem.
8. Valide homepage, Dashboard, `#visual-system`, fallback, elemento flutuante e `/api/v1/health`.
9. Consulte logs privados e registre causa da reversão.
