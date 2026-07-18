# Geração e validação dos manifestos

## Comandos

```sh
php scripts-build/generate-quadrant-slots.php
php scripts-build/generate-quadrant-slots.php --check
php scripts-build/generate-quadrant-slots.php --force
php scripts-build/validate-story-manifests.php
```

O modo padrão recalcula estrutura e preserva campos editoriais existentes. `--check` não grava e falha quando o conteúdo determinístico difere. `--force` solicita reinicialização completa, mas não sobrescreve slots publicados. A escrita usa arquivo temporário após validação interna.

O validador usa apenas PHP padrão. Ele analisa todo JSON e os schemas, cardinalidades, IDs, posições, famílias, tipos e limites de parâmetros, referências controle/Widget/versão/quadrante, perfis temporais, continuidade, sarjetas, fórmula e caminhos de slots.

Mensagens `[ERRO]` identificam arquivo ou entidade e produzem código diferente de zero. Corrija primeiro o registro canônico, depois todas as referências; regenere slots quando quadrantes ou versões mudarem. Não edite o produto cartesiano manualmente.

Uma entrega é aprovada somente quando `--check` e a validação terminam com `[OK]`, sem advertências estruturais, e o diff não contém arquivos do front-end. JSON válido isoladamente não basta: referências, posições e cardinalidades também precisam ser aprovadas.
