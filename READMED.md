# Compact Tabs

Compact Tabs é um plugin para GLPI que reduz a poluição visual das telas com abas verticais, ocultando automaticamente abas vazias e mantendo visíveis apenas as abas relevantes para navegação e operação.

Este plugin foi desenvolvido e testado inicialmente no GLPI 10.0.20.

## Status do projeto

Versão atual em desenvolvimento:

```text
0.1.1 functional snapshot
```

Esta versão está funcional, mas ainda será refatorada para aderir melhor aos padrões recomendados de desenvolvimento de plugins GLPI antes de uma versão estável.

## Recursos disponíveis

O plugin permite compactar abas nas seguintes telas:

```text
Chamados
Problemas
Mudanças
Solicitações do FormCreator
```

Comportamento atual:

```text
Aba principal sempre visível
Aba ativa sempre visível
Aba Todos sempre visível
Abas com contador maior que zero sempre visíveis
Abas configuradas como sempre visíveis permanecem visíveis mesmo vazias
Demais abas ficam ocultas até o usuário expandir
```

## Recursos implementados

```text
Compactação de abas em Chamados
Compactação de abas em Problemas
Compactação de abas em Mudanças
Compactação de abas em Solicitações do FormCreator
Botão para mostrar ou recolher abas ocultas
Configuração por tela
Descoberta automática de abas nativas e de plugins
Exceções configuráveis por aba
Suporte a tabKeys, independente do idioma da interface
Histórico de alterações de configuração
Paginação no histórico
Suporte temporário à descoberta via interface simplificada do FormCreator
```

## Telas atendidas

### Chamados

Tela nativa do GLPI:

```text
/front/ticket.form.php
```

### Problemas

Tela nativa do GLPI:

```text
/front/problem.form.php
```

### Mudanças

Tela nativa do GLPI:

```text
/front/change.form.php
```

### Solicitações do FormCreator

Tela simplificada do plugin FormCreator:

```text
/plugins/formcreator/front/issue.form.php
```

## Instalação manual

Copie o diretório do plugin para:

```bash
/usr/share/glpi/plugins/compacttabs
```

Ajuste proprietário e permissões conforme o ambiente:

```bash
cd /usr/share/glpi/plugins

chown -R apache:apache compacttabs
find compacttabs -type d -exec chmod 755 {} \;
find compacttabs -type f -exec chmod 644 {} \;
```

Instale e ative o plugin:

```bash
cd /usr/share/glpi

glpi-console glpi:plugin:install compacttabs --force
glpi-console glpi:plugin:activate compacttabs
glpi-console cache:clear
```

Caso o comando `glpi-console` não esteja disponível:

```bash
cd /usr/share/glpi

sudo -u apache php bin/console glpi:plugin:install compacttabs --force
sudo -u apache php bin/console glpi:plugin:activate compacttabs
sudo -u apache php bin/console cache:clear
```

## Configuração

Acesse no GLPI:

```text
Configurar > Plugins > Compact Tabs > Configurar
```

Na aba Geral, é possível habilitar ou desabilitar:

```text
Chamados
Problemas
Mudanças
Solicitações do FormCreator
Descoberta automática de abas
Descoberta via usuários da interface simplificada do FormCreator
```

Nas abas de configuração específicas, é possível marcar quais abas devem permanecer visíveis mesmo sem contador.

## Descoberta automática de abas

O Compact Tabs detecta abas com base no `tabKey` presente no `href` das abas do GLPI.

Exemplo:

```text
/ajax/common.tabs.php?_glpi_tab=PluginPdfTicket%241
```

Internamente, o JavaScript interpreta como:

```text
PluginPdfTicket$1
```

Isso evita dependência do texto exibido na interface e permite compatibilidade com diferentes idiomas do GLPI.

## Histórico

O plugin possui histórico próprio de alterações de configuração.

A tabela utilizada é:

```text
glpi_plugin_compacttabs_histories
```

O histórico registra:

```text
Data
Usuário
Ação
Contexto
Campo alterado
Valor anterior
Novo valor
```

## Observações sobre o FormCreator

Em alguns perfis com interface simplificada, o usuário acessa solicitações por:

```text
/plugins/formcreator/front/issue.form.php
```

Em perfis Super-Admin, o GLPI pode redirecionar para:

```text
/front/ticket.form.php
```

Por isso existe uma opção temporária para permitir descoberta de abas pela interface simplificada do FormCreator. Após a descoberta das abas, recomenda-se desativar essa opção.

## Logs

O plugin utiliza o arquivo:

```text
/var/log/glpi/compacttabs.log
```

Logs PHP do GLPI:

```text
/var/log/glpi/php-errors.log
```

Logs de acesso negado ou CSRF:

```text
/var/log/glpi/access-errors.log
```

## Compatibilidade

Testado inicialmente com:

```text
GLPI 10.0.20
FormCreator em ambiente GLPI 10
Interface padrão e interface simplificada
```

## Próximos passos planejados

```text
Refatorar código para classes em src/
Reduzir lógica dentro de front/config.form.php
Mover CSS e JavaScript inline para arquivos próprios
Ajustar instalação usando Migration conforme padrão GLPI
Adicionar filtros ao histórico
Adicionar exportação CSV do histórico
Criar pacote release para GitHub
```

## Licença

GPL-3.0-or-later.
