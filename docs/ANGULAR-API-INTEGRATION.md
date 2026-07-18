# ANGULAR API INTEGRATION

Tarefa 6 integra Dashboard, Angular e API mantendo o Dashboard como emissor de eventos sem credenciais, Angular como estado central da ilha em `#visual-system` e API PHP/MySQL como autoridade remota.

## Contrato implementado

- Eventos públicos usam `schemaVersion: 1.0.0` e nunca carregam token.
- Snapshot inicial: `dashboard:state-snapshot` com 32 controles e quatro Widgets.
- Restauração: `tna:dashboard-restore-state` aplica estado silenciosamente via `applyExternalState(..., { emitEvents: false })`.
- Persistência híbrida usa `tna:session:v1` para token anônimo isolado e `tna:story-state:v1` para cache/fila sem duplicar slots.
- Debounce de persistência: 350 ms; story run estabiliza em 800 ms após sincronização.
- Conflitos 409 entram em `conflict`; a política documentada é reconciliação por campo com último timestamp, limitada até clocks lógicos futuros.
- Offline mantém seleções locais, fila limitada a 60 operações e mensagem discreta com `aria-live=polite`.

## Segurança e rollback

O Dashboard não chama `fetch('/api/...')`. O token é mantido fora do DOM, de eventos públicos e de logs. Rollback seguro: remover `scripts/dashboard-story-bridge.js` de `index.html` e reconstruir o bundle Angular anterior.
