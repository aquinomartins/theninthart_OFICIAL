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

A mensagem era criada em `scripts/tempo.js` e `scripts/experience.js` no `catch` global de `boot()`. A implementação anterior ocultava a origem real do erro e exibia apenas “A experiência carregou em modo básico porque um recurso falhou.”.

Problemas encontrados:

1. O menu apontava para `experience.html`, mas o arquivo não existia, causando 404 na navegação.
2. A rota `/tempo/` não tinha `index.html`, deixando a URL publicada insegura.
3. O manifesto de assets apontava para a pasta inexistente `imagensPrompt/`, gerando imagens quebradas.
4. `loadJson()` usava caminhos relativos (`data/...`), o que quebraria dados quando a experiência fosse carregada por uma rota de subdiretório.

Correção aplicada:

- `asset-manifest.js` agora referencia imagens existentes em `/tempo/images/`.
- O elemento flutuante no HTML usa `/tempo/images/imagem1.png`.
- `loadJson()` resolve dados a partir da origem do site.
- O `catch` global registra recurso, erro, stack, página e user agent antes de exibir fallback.
- `experience.html` foi restaurado como entrada direta.
- `tempo/index.html` foi criado como rota de compatibilidade.

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
