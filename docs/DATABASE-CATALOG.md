# Catálogo narrativo no MySQL

A migration `002_story_catalog.sql` cria a estrutura relacional inicial do catálogo narrativo. Esta fase não inclui seeds, endpoints, sessões, eventos, usuários, story runs, estado público nem integração Angular.

## Tabelas

### `story_controls`

Armazena os 32 controles narrativos como catálogo estrutural futuro. Campos JSON usam `LONGTEXT` para validação posterior no PHP.

Índices únicos:

- `uq_story_controls_stable_key` em `stable_key`;
- `uq_story_controls_position` em `position`.

### `story_widgets`

Armazena os quatro Widgets do Dashboard como catálogo estrutural futuro, mantendo IDs de ativação e painéis como contratos únicos.

Índices únicos:

- `uq_story_widgets_stable_key` em `stable_key`;
- `uq_story_widgets_position` em `position`;
- `uq_story_widgets_panel_id` em `panel_id`;
- `uq_story_widgets_activation_button_id` em `activation_button_id`.

### `story_versions`

Armazena famílias narrativas versionadas, perfis e preferências em campos `LONGTEXT` destinados a JSON.

Índices únicos:

- `uq_story_versions_stable_key` em `stable_key`;
- `uq_story_versions_version_number` em `version_number`;
- `uq_story_versions_position` em `position`.

### `quadrants`

Armazena os quadrantes canônicos, sua posição, função narrativa e contratos estruturais de imagem/legenda/diálogo.

Índices únicos:

- `uq_quadrants_stable_key` em `stable_key`;
- `uq_quadrants_number` em `number`;
- `uq_quadrants_position` em `position`.

### `quadrant_variants`

Relaciona um quadrante a uma versão narrativa, formando slots como `qNN-vNN`.

Índices únicos:

- `uq_quadrant_variants_slot_key` em `slot_key`;
- `uq_quadrant_variants_quadrant_version` em `quadrant_id, story_version_id`;
- `uq_quadrant_variants_position` em `position`.

Foreign keys:

- `fk_quadrant_variants_quadrant`: `quadrant_id` referencia `quadrants(id)`;
- `fk_quadrant_variants_story_version`: `story_version_id` referencia `story_versions(id)`.

### `media_assets`

Armazena metadados de mídia por variante. Não armazena imagem binária. `asset_path` pode ser nulo; `expected_path` é obrigatório.

Índice único:

- `uq_media_assets_quadrant_variant_id` em `quadrant_variant_id`, garantindo um asset por variante nesta fase.

Foreign key:

- `fk_media_assets_quadrant_variant`: `quadrant_variant_id` referencia `quadrant_variants(id)`.

## Engine e charset

Todas as tabelas do catálogo usam:

- `ENGINE=InnoDB`;
- `DEFAULT CHARSET=utf8mb4`;
- `COLLATE=utf8mb4_unicode_ci`.

## Verificação estrutural

Execute:

```sh
php scripts-build/migrate.php verify
```

A verificação não exige dados nesta fase. Ela confere migrations, checksums, seis tabelas do catálogo, índices únicos, foreign keys, engine e charset.
