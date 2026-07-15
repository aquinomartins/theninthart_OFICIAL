# The Ninth Art

Site estático da iniciativa **The Ninth Art**, com a experiência interativa Tempo e uma cozinha pública temporal construída em HTML, CSS e JavaScript sem framework.

## Estrutura

- `index.html`: página pública principal e rota `/` / `/index.html` / `/tempo.html` quando publicada como arquivo raiz.
- `experience.html`: entrada direta preservada para a experiência interativa apontada pelo menu.
- `tempo/index.html`: rota de compatibilidade `/tempo/`, redirecionando para a experiência principal.
- `styles.css`: estilos globais da página institucional.
- `styles/`: estilos específicos da experiência Tempo, componentes e motion.
- `script.js`: interações globais da página inicial, como reveal e carrossel visual.
- `scripts/tempo.js` e `scripts/experience.js`: inicialização da experiência Tempo.
- `scripts/modules/`: módulos compartilhados de carrossel, cozinha, estado local, painel admin e motor temporal.
- `data/`: JSONs consumidos pela experiência.
- `tempo/images/`: assets públicos atualmente referenciados por HTML e JavaScript.
- `docs/`: auditoria técnica e relatório de remoções.

## Páginas públicas

| Rota | Arquivo | Observação |
|---|---|---|
| `/` | `index.html` | Página principal e experiência Tempo integrada. |
| `/index.html` | `index.html` | Entrada direta equivalente. |
| `/experience.html` | `experience.html` | Entrada preservada porque o menu apontava para ela. |
| `/tempo/` | `tempo/index.html` | Compatibilidade para URLs publicadas em diretório. |

## Como executar localmente

```bash
python3 -m http.server 4173
```

Depois acesse `http://127.0.0.1:4173/`.

## Publicação

Publique o conteúdo da raiz do repositório como site estático. As referências críticas de dados e imagens usam caminhos absolutos a partir da origem (`/data/...`, `/tempo/images/...`) para funcionar em `/`, `/experience.html` e `/tempo/`.

## Dependências

Não há `package.json` nem dependências npm obrigatórias. Os testes básicos usam Python e Node instalados no ambiente.

## Armazenamento local

A experiência usa `localStorage` por meio de `scripts/modules/state-store.js` para sessões, snapshot público e estado de credenciais. O módulo possui fallback em memória quando o armazenamento não está disponível.

## Service worker

Não existe service worker neste repositório. Se a produção apresentar cache obsoleto, a origem provável é configuração externa de hospedagem ou um worker previamente publicado fora do código atual.

## Assets

Use `tempo/images/` para imagens públicas já existentes. Ao adicionar imagens, prefira nomes em `lowercase-kebab-case.ext`, evite espaços, acentos e parênteses, e atualize `scripts/modules/asset-manifest.js` quando o asset for consumido por JavaScript.

## Como adicionar uma nova página

1. Crie o HTML na raiz ou em um diretório com `index.html`.
2. Carregue apenas CSS/JS necessários para a página.
3. Use caminhos absolutos para assets compartilhados (`/tempo/images/...`, `/data/...`) quando a página puder ser acessada de subdiretórios.
4. Adicione a rota à tabela de páginas públicas e teste com servidor local.

## Como testar

- `node --check script.js scripts/*.js scripts/modules/*.js tempo/script/*.js`
- `python3 -m http.server 4173`
- Validar `/`, `/index.html`, `/experience.html` e `/tempo/` com cache limpo.

## Problemas conhecidos

- Testes reais em Chrome, Firefox e Edge dependem dos navegadores estarem disponíveis no ambiente.
- `scripts/tempo.js` e `scripts/experience.js` ainda são duplicados por compatibilidade; consolidação pode ser feita em uma etapa futura com testes de regressão.
