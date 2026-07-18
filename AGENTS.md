# Instruções persistentes para o Codex

Estas regras abrangem todo o repositório.

- Preserve o front-end e o comportamento atuais. Não altere a identidade visual e não remova seções sem autorização expressa.
- Mantenha o elemento `#visual-system`, seu contrato de seletores e seu fallback estático.
- Mantenha o elemento flutuante (`imagem1.png`) e seus fluxos de ida ao sistema visual e retorno à origem.
- Mantenha os 32 controles liga/desliga e os quatro Widgets do Dashboard, inclusive IDs e atributos `data-*` usados como contratos.
- Evite bibliotecas visuais externas; reutilize os estilos e componentes existentes.
- A pilha autorizada é HTML5, CSS, JavaScript, Angular, PHP e MySQL.
- Mantenha Angular isolado: monte-o apenas dentro de `#visual-system`; ele não deve controlar cabeçalho, hero, Dashboard, manifesto, demais seções ou footer.
- Node pode ser usado no desenvolvimento/build, mas não será exigido em produção.
- PHP e MySQL devem funcionar em hospedagem compartilhada e não podem depender de processos residentes.
- Nunca inclua credenciais, segredos ou configurações privadas versionadas. Use exemplos sem valores sensíveis e configuração fora do web root quando disponível.
- Execute testes relevantes antes de concluir.
- Produza alterações pequenas, independentes, revisáveis e com caminho de rollback claro.
