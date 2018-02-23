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

- No modo Fornecedor ocorre o inverso: os produtos são enviados para a Epicom e os pedidos são recebidos no Magento.

É possível também ativar a Autenticação Básica HTTP protegendo assim o endpoint de usuários maliciosos.

Observações: A comunicação da loja Magento e do serviço MHub da Epicom são feitos automaticamente via chamadas webhook e cron.

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-ajustes.png" alt="" title="Epicom MHub - Magento - Habilitando o módulo no Painel Administrativo" />

**2 - Gerenciando as categorias**

É possível selecionar qual categoria irá para a Epicom, no momento do cadastro, juntamente com todos os seus produtos.

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-categorias-admin.png" alt="" title="Epicom MHub - Magento - Gerenciando as categorias" />

**3 - Consultando a fila de categorias**

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-categorias-grid.png" alt="" title="Epicom MHub - Magento - Consultando a fila de categorias" />

**4 - Consultando a fila de marcas**

As opções do *Atributo de Marca* previamente selecionado na configuração do painel administrativo, são automaticamente enviadas para a Epicom.

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-marcas-grid.png" alt="" title="Epicom MHub - Magento - Consultando a fila de marcas" />

**5 - Consultando a fila de grupos de atributos**

As configurações dos atributos multi-valorados e os agrupamentos destes atributos, podem ser mapeados antes de serem enviados para a Epicom.

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-grupos_atributos-grid.png" alt="" title="Epicom MHub - Magento - Consultando a fila de grupos de atributos" />

**6 - Consultando a fila de produtos**

Os produtos das categorias que tiveram a opção *Enviar Produtos* marcadas no painel administrativo, serão automaticamente enviados para a Epicom.

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-produtos-grid.png" alt="" title="Epicom MHub - Magento - Consultando a fila de grupos de atributos" />

**7 - Gerenciando os produtos no painel**

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-produtos-painel.png" alt="" title="Epicom MHub - Magento - Gerenciando os produtos no painel" />

Através do painel podemos *Associar os Produtos* para diversos canais incluindo o próprio Magento.

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-produtos-associacao.png" alt="" title="Epicom MHub - Magento - Associando os produtos no painel" />

E editar os detalhes do produto diretamente no painel da Epicom.

<img src="https://s3-us-west-2.amazonaws.com/githubepicom/mhub_magento/epicom-magento-mhub-produtos-detalhes.png" alt="" title="Epicom MHub - Magento - Editando os produtos no painel" />

