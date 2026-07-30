# Clonar um relatório a partir do detalhe

## Problema

O menu da lista de Reports já permitia clonar uma configuração, mas quem começava pelo detalhe precisava voltar à lista, localizar o mesmo relatório e abrir o menu da linha.

## Solução

O detalhe agora mostra `Clone` junto das ações existentes. O link reutiliza a rota, a verificação de acesso e o formulário de clonagem atuais. O clone continua sendo um formulário não salvo: a pessoa revisa o nome e as opções e decide explicitamente se quer salvar.

## Fluxo validado

- Antes: abrir a lista, localizar o relatório, abrir `Options` e escolher `Clone` — 4 ações.
- Depois: abrir o detalhe e escolher `Clone` — 2 ações no caminho completo; uma ação para a decisão de reaproveitar a configuração.

Nenhum relatório existente é alterado, publicado ou apagado ao abrir a ação.
