#Correios

Um pacote para interagir com serviços dos Correios.

##Recursos

* Calcula preços e prazos de entrega;
* Encontra endereço a partir de um CEP;
* Pega dados de rastreio de pacotes.

Acha que está faltando algo? [Abra um issue](https://github.com/eberfreitas/correios/issues/new)!

##Por quê?

Já existem [diversos pacotes](https://packagist.org/search/?q=correios) que
trabalham com os serviços dos Correios, mas sempre existia algo que me impedia
de usar algum dele, seja porque ele havia sido feito especificamente para algum
software ou framework, ou porque ele não tinha todos os recursos que eu
precisava, etc. A ideia então foi tentar um novo pacote com os seguintes
objetivos:

* **Stand-alone**: para ser usado em qualquer projeto/framework com o mínimo de
  dependências extras possível;
* **Feature complete**: tentar cobrir o máximo de recursos que os Correios
  oferecem através de APIs oficiais ou não;
* **PSR compliant**: O autoload é compatível com PSR-4 e a formatação PSR-2;
* **Testes**: Tentar testar a biblioteca o máximo possível.

##Instalação

Pelo Composer:

``` bash
$ composer require eberfreitas/correios
```

##Como usar

Para usar a biblioteca, antes de tudo, você precisa criar uma instância:

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
  mais sobre este mais à frente na documentação.

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

###Encontrando um endereço via CEP

Para encontrar um endereço a partir de um CEP, basta usar o método `endereco`:

``` php
$endereco = $correios->endereco('70150900');

print_r($endereco);
```

Que vai resultar neste output:

```
Array
(
    [logradouro] => Praça dos Três Poderes
    [logradouro_extra] =>
    [bairro] => Zona Cívico-Administrativa
    [cidade] => Brasília
    [uf] => DF
    [cep] => 70150900
)
```

* `logradouro` - Aqui você encontra o nome da rua, avenida, praça, etc;
* `logradouro_extra` - Eventualmente algum CEP vai trazer informações extras
  com o lado (ímpar ou par) a quele ele corresponde naquele logradouro ou qual
  a faixa de numeração (de 1001 a 2000);
* `bairro` - O bairro deste CEP;
* `cidade` - A cidade;
* `uf` - A sigla do estado;
* `cep` - A repetição do CEP consultado.

No caso de falha na consulta serão emitidos exceptions. Fique atento para
pegá-los quando necessário.

###Rastreando um pacote

...

##Créditos