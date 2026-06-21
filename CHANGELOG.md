# Changelog

Todas as mudanças relevantes deste projeto serão documentadas neste arquivo.

O formato segue uma estrutura simples inspirada em Keep a Changelog.

## [0.1.1-functional-snapshot] - 2026-06-21

### Adicionado

```text
Compactação de abas na tela de Chamados
Compactação de abas na tela de Problemas
Compactação de abas na tela de Mudanças
Compactação de abas na tela de Solicitações do FormCreator
Botão para mostrar ou recolher abas ocultas
Configuração por tela
Descoberta automática de abas nativas e abas de plugins
Suporte a tabKeys, independente do idioma da interface
Configuração de abas sempre visíveis
Histórico de alterações de configuração
Coluna de contexto no histórico
Paginação na aba Histórico
Seletor de linhas por página no histórico
Suporte temporário à descoberta por usuários da interface simplificada do FormCreator
```

### Corrigido

```text
Correção de CSRF no salvamento da configuração
Correção do clique múltiplo no botão de expandir/recolher
Redução de flicker ao navegar entre chamados
Correção da descoberta automática para Problemas e Mudanças
Correção da descoberta automática para Solicitações do FormCreator
Correção da persistência de abas sempre visíveis do FormCreator
Correção da renderização da aba Histórico
Correção do botão Salvar configuração na aba Histórico
Correção do checkbox de selecionar tudo para atuar somente na tabela ativa
```

### Alterado

```text
A tela de configuração foi organizada em abas laterais
As abas detectadas passaram a ser exibidas em ordem alfabética
O histórico passou a registrar uma linha por alteração
Valores do histórico passaram a usar 0 e 1 internamente
A interface do histórico exibe Ativado (1) e Desativado (0)
```

### Técnico

```text
Criada tabela glpi_plugin_compacttabs_histories
Criado endpoint de descoberta front/discover.form.php
Criado endpoint de configuração JS front/compacttabs.config.js.php
Criado arquivo inc/history.function.php
```

## [0.1.0-dev] - Desenvolvimento inicial

### Adicionado

```text
Primeira versão funcional para compactação de abas em Chamados
Carregamento de CSS e JavaScript pela tela de chamados
Botão de alternância para abas ocultas
Primeira tela simples de configuração
```
