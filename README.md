#Correios

Um pacote para interagir com serviços dos Correios.

##Objetivos

* Stand-alone: para ser usado em qualquer projeto/framework com o mínimo de
  dependências possível;
* Feature complete: cobrir o máximo de recursos possíveis que os Correios
  oferecem através de APIs oficiais ou não;
* PSR compliant: O autoload é compatível com PSR-4 e a formatação PSR-2;
* Testes: Tentar testar a biblioteca o máximo possível.

##O que essa biblioteca faz?

* Calcula preços e prazos de entrega;
* Encontra endereço a partir de um CEP;
* Pega dados de rastreio de pacotes.

##Instalação

Pelo Composer

``` bash
$ composer require eberfreitas/correios
```

##Como usar

Esta biblioteca pode realizar 3 tarefas diferentes relacionadas aos serviços
dos Correios. Para usar a biblioteca, antes de tudo, você precisa criar uma
instância:

``` php
$correios = new Correios\Correios();
```

Isto já deve ser suficiente para começar a utilizar a biblioteca. Opcionalmente
você pode configurar a sua instância de forma diferente. O construtor da classe
aceita um array com as seguintes chaves:

* `usuario` - Se você tiver contrato com os Correios, pode informar seu nome de
  usuário aqui.
* `senha` - Sua senha, caso tenha um usuário com cadastro nos Correios.
* `http_adapter` - Aqui você pode passar um objeto que será responsável por
  realizar as requisições HTTP de consulta aos webservices dos Correios. Veja
  mais sobre este recurso na documentação.

Exemplo:

``` php
$adaptador = new MinhaClasseHttp;

$correios = new Correios\Correios([
    'usuario' => 'joao',
    'senha' => 'segredo',
    'http_adapter' => $adaptador
]);
```

Embora você tenha a opção de providenciar um adaptador, se você não indicar
nenhum, a biblioteca irá utilizar o `GuzzleHttp` por padrão.

##Créditos