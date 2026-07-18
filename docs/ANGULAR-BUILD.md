# Build Angular

Requisitos locais: Node 20+, npm e acesso ao pacote Angular definido em `frontend/story-engine/package.json`. Produção em hospedagem compartilhada recebe apenas arquivos estáticos de `assets/story-engine/`.

```sh
cd frontend/story-engine
npm install
npm run build
npm run build:host
```

O build de produção sai em `frontend/story-engine/dist/story-engine/browser/`. O script `scripts-build/build-story-engine.js` limpa com segurança `assets/story-engine/`, copia apenas `main*.js`, `polyfills*.js` e `styles*.css`, ignora source maps, `node_modules`, testes, configuração e o `index.html` Angular. Em seguida `scripts-build/generate-story-engine-assets.js` cria `assets/story-engine/story-engine-assets.json` com os caminhos versionados.

Orçamento inicial: aviso em 260 kB e erro em 420 kB para o bundle inicial. O `story-engine-assets.json` deve ser revalidado a cada deploy para evitar HTML com nomes hashados.
