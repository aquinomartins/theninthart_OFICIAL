# Relatório de remoções

Nenhum arquivo de produto foi removido nesta auditoria. A regra adotada foi preservar qualquer item sem evidência conclusiva de inutilização.

| Arquivo | Motivo | Evidência de não utilização | Risco |
|---|---|---|---|
| Nenhum | Nenhuma remoção segura comprovada nesta etapa | Busca global, inventário e validação funcional indicaram candidatos ambíguos, não remoções seguras | N/A |

## Candidatos mantidos para revisão humana

| Arquivo | Motivo para revisar | Evidência atual | Decisão |
|---|---|---|---|
| `tempo/script/main.js` | Não é referenciado por nenhuma rota HTML atual | Busca global não encontrou import/link ativo | Mantido por precaução |
| `tempo/style/main.css` | Não é referenciado por nenhuma rota HTML atual | Busca global não encontrou link ativo | Mantido por precaução |
| `styles/tempo.tokens.css` | Import está comentado em `styles/tempo.css` | Tokens podem ser úteis e há variáveis compartilhadas | Mantido por precaução |
