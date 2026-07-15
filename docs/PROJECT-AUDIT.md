# Auditoria do projeto

## Estado inicial

- Branch inicial: `work`.
- Commit inicial registrado: `18941720a04a3172e9210c4975325f31d567ec2e`.
- Branch de trabalho criada: `chore/project-audit-cleanup`.
- Inventário inicial salvo em `docs/INVENTORY-BEFORE.txt`.
- Não havia `package.json`, service worker, manifest web app ou build step.

## Páginas e rotas públicas

| Página | Arquivo | CSS | JavaScript | Dados | Storage | Status |
|---|---|---|---|---|---|---|
| `/` | `index.html` | `styles.css`, `styles/tempo.css` | `script.js`, `scripts/tempo.js` | `data/*.json` | `localStorage` | Em uso |
| `/index.html` | `index.html` | `styles.css`, `styles/tempo.css` | `script.js`, `scripts/tempo.js` | `data/*.json` | `localStorage` | Em uso |
| `/experience.html` | `experience.html` | `styles.css`, `styles/tempo.css` | `script.js`, `scripts/experience.js` | `data/*.json` | `localStorage` | Restaurada |
| `/tempo/` | `tempo/index.html` | Nenhum | Nenhum | Nenhum | Nenhum | Compatibilidade |

## Causa da mensagem de modo básico

A mensagem era criada em `scripts/tempo.js` e `scripts/experience.js` no `catch` global de `boot()`. Na página atual, a primeira falha real ocorria quando a inicialização tentava escrever em controles opcionais que não existem no HTML publicado, começando por `[data-hero-subtitle]`.

Cadeia confirmada:

1. `renderAll()` executava `qs('[data-hero-subtitle]').textContent = ...`.
2. Como o seletor não existe em `index.html` nem em `experience.html`, `querySelector()` retornava `null`.
3. O acesso a `textContent` gerava `TypeError: Cannot set properties of null (setting 'textContent')`.
4. A Promise de `boot()` rejeitava.
5. O `catch` global ativava o modo básico e exibia o aviso vermelho.

Correção aplicada:

- Campos textuais de painéis opcionais agora são atualizados apenas quando existem no DOM.
- O carrossel de detalhes e a imagem de peça foram classificados como opcionais, então a ausência desses elementos não derruba a experiência global.
- A cozinha principal (`[data-kitchen-scene]`) permanece essencial e gera `ExperienceResourceError` estruturado se estiver ausente.
- `loadJson()` valida status HTTP, `Content-Type: application/json` e sintaxe do JSON antes de entregar dados essenciais.
- O fallback mantém diagnóstico técnico no console e mostra uma mensagem segura ao visitante com botão “Tentar novamente”.
- A inicialização passou a usar uma Promise idempotente para permitir nova tentativa sem duplicar listeners globais.
- Não há service worker no projeto; portanto, nenhuma lista de precache ou estratégia de cache foi alterada.

## Inventário resumido

| Caminho | Tipo | Utilizado por | Status | Ação sugerida |
|---|---|---|---|---|
| `index.html` | HTML | `/`, `/index.html` | Em uso | Manter |
| `experience.html` | HTML | `/experience.html` | Em uso | Manter |
| `tempo/index.html` | HTML | `/tempo/` | Compatibilidade | Manter |
| `styles.css` | CSS | `index.html`, `experience.html` | Compartilhado | Manter |
| `styles/tempo.css` | CSS | `index.html`, `experience.html` | Compartilhado | Manter |
| `styles/tempo.components.css` | CSS | `styles/tempo.css` | Compartilhado | Manter |
| `styles/tempo.motion.css` | CSS | `styles/tempo.css` | Compartilhado | Manter |
| `styles/tempo.tokens.css` | CSS | não importado ativamente | Legado/precaução | Manter até revisão visual dedicada |
| `script.js` | JS | páginas principais | Em uso | Manter |
| `scripts/tempo.js` | JS módulo | `index.html` | Em uso | Manter |
| `scripts/experience.js` | JS módulo | `experience.html` | Duplicado necessário | Consolidar futuramente |
| `scripts/modules/*.js` | JS módulos | experiência | Compartilhado | Manter |
| `data/*.json` | JSON | experiência | Em uso | Manter |
| `tempo/images/*` | Imagens | HTML e asset manifest | Em uso/compartilhado | Manter |
| `tempo/script/main.js` | JS | nenhuma rota atual | Aparentemente órfão | Manter por precaução |
| `tempo/style/main.css` | CSS | nenhuma rota atual | Aparentemente órfão | Manter por precaução |

## Arquivos removidos

Nenhum arquivo de produto foi removido. A auditoria não encontrou evidência suficiente para exclusões seguras sem validação humana adicional.

## Arquivos movidos

- O inventário temporário gerado pela auditoria foi movido para `docs/INVENTORY-BEFORE.txt`.

## Arquivos mantidos por precaução

- `tempo/script/main.js` e `tempo/style/main.css`: parecem ser uma versão independente/legada da experiência, mas podem estar ligados a URLs históricas ou servir de referência visual.
- `styles/tempo.tokens.css`: não é importado ativamente porque `styles/tempo.css` mantém o import comentado, mas contém tokens úteis para uma futura separação de CSS.

## Duplicações

- `scripts/tempo.js` e `scripts/experience.js` são duplicados. A duplicação foi preservada para evitar mudança arquitetural ampla; ambos receberam a correção de carregamento e diagnóstico.
- Várias imagens são reutilizadas em diferentes slots visuais. Não foram removidas porque funcionam como placeholders/variações editoriais.

## Testes executados

- Checagem sintática de JavaScript com Node.
- Servidor estático local com Python.
- Validação HTTP das rotas `/`, `/index.html`, `/experience.html`, `/tempo/` e recursos diretos carregados por HTML.
- Parse JSON dos arquivos de dados.

## Pendências

- Executar validação manual real em Chrome, Firefox e Edge com DevTools.
- Consolidar `scripts/tempo.js` e `scripts/experience.js` em um módulo compartilhado quando houver cobertura de regressão.
- Decidir se `tempo/script/main.js`, `tempo/style/main.css` e `styles/tempo.tokens.css` devem ser arquivados em uma próxima etapa.
