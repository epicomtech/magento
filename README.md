<h1>Módulo de Integração com Marketplaces</h1>

**Compatível com a plataforma Magento versão 1.x**

<h2>Instalação</h2>

**Instalar usando o modgit:**

    $ cd /path/to/magento
    $ modgit init
    $ modgit add epicomtech_magento https://github.com/epicomtech/magento.git

<h2>Conhecendo o módulo</h2>

**1 - Habilitando o módulo MHub**

Nesta tela é possível habilitar o módulo MHub em sua loja, e escolher também qual será o modo de funcionamento: Marketplace ou Fornecedor.

- No modo Marketplace, os produtos são recebidos da Epicom diretamente para o Magento, e os pedidos são enviados para a Epicom.

- No modo Fornecedor acontece o inverso: os produtos são enviados para a Epicom e os pedidos são recebidos no Magento.

É possível também ativar a Autenticação Básica HTTP protegendo assim o endpoint de usuários maliciosos.

Observações: A comunicação da loja Magento e do serviço MHub da Epicom são feitos automaticamente via chamadas webhook e cron.

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-ajustes.png" alt="" title="Epicom MHub - Magento - Habilitando o módulo no Painel Administrativo" />

