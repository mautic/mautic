# Filtro de usuários que nunca acessaram

Na lista de usuários, o filtro rápido **Never logged in** encontra contas cujo
campo **Last Login** ainda está vazio. Ele é somente de leitura e ajuda a
revisar convites ou contas que ainda não foram usadas sem percorrer a coluna
manualmente.

Para testar:

1. Abra **Settings > Users**.
2. Abra **Quick filters** e escolha **Never logged in**.
3. Confirme que a busca usa `is:never_logged_in` e mostra somente usuários sem
   data de último acesso.
4. Use **Reset** para voltar à lista completa.
