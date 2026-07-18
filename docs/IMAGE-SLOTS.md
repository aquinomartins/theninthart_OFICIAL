# Slots de imagens

## Convenção

O produto cartesiano canônico é **29 quadrantes × 7 versões = 203 slots**, em ordem de quadrante e depois versão. A posição é `((quadrantNumber - 1) * 7) + versionNumber`; o primeiro slot é `q01-v01` e o último, `q29-v07`.

O caminho esperado segue `/assets/story/quadrants/qNN/vNN/qNN-vNN.png`, por exemplo `/assets/story/quadrants/q18/v04/q18-v04.png`. Ele é planejamento, não prova de existência do asset. `asset.path` começa nulo. PNG é a convenção inicial; o caminho editorial futuro pode apontar para PNG, WebP, AVIF, JPEG ou SVG tecnicamente adequado.

Estados: `empty`, `planned`, `draft`, `review`, `approved`, `published` e `archived`. A revisão avança o estado, registra revisão/checksum/dimensões e só publica após aprovação editorial. O texto alternativo inicial identifica quadrante, versão e pendência; ao inserir imagem real deve descrever seu conteúdo e função narrativa, sem depender da legenda.

O modo padrão do gerador preserva dados editoriais. `--force` reinicializa conscientemente e é recusado se houver slot publicado. Não se criam diretórios, imagens ou tags HTML nesta fase.

Para adicionar V08, cadastre a versão e amplie os limites/contagens dos contratos antes de regenerar; componentes consumidores devem iterar IDs, não codificar sete itens. A fórmula deverá usar a nova cardinalidade versionada. Verifique ausências e divergências com `php scripts-build/generate-quadrant-slots.php --check` e depois execute o validador.
