# TNA Story Engine

Aplicação Angular standalone montada somente pelo seletor `tna-story-engine`, dentro de `#visual-system`. O site principal continua HTML/CSS/JavaScript; Node é usado apenas para desenvolvimento, teste e build.

## Comandos

```sh
cd frontend/story-engine
npm install
npm start
npm run build
npm run build:host
npm test
```

`npm run build:host` compila Angular e copia somente os bundles públicos para `assets/story-engine/`, gerando `story-engine-assets.json`.
