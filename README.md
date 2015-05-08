#Correios

Um pacote para interagir com serviços dos Correios.

##Recursos

* [Encontra endereço a partir de um CEP](#encontrando-um-endereço-via-cep);
* [Pega dados de rastreio de pacotes](#rastreando-um-pacote);
* [Calcula preços e prazos de entrega](#calculando-valor-de-frete-e-prazo-de-entrega).

Acha que está faltando algo? [Abra um issue!](https://github.com/eberfreitas/correios/issues/new)

##Por quê?

Já existem [diversos pacotes](https://packagist.org/search/?q=correios) que
trabalham com os serviços dos Correios, mas sempre existia algo que me impedia
de usar algum deles, seja porque ele havia sido feito especificamente para algum
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

Chave          | Descrição
---------------|----------
`usuario`      | Se você tiver contrato com os Correios, pode informar seu nome de usuário aqui.
`senha`        | Sua senha, caso tenha um usuário com cadastro nos Correios.
`http_adapter` | Aqui você pode passar um objeto que será responsável por realizar as requisições HTTP de consulta aos webservices dos Correios. Saiba mais sobre isto [aqui](#usando-um-http-adapter-diferente).

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
nenhum, a biblioteca irá utilizar o `Guzzle` por padrão.

###Encontrando um endereço via CEP

Para encontrar um endereço a partir de um CEP, basta usar o método `endereco`:

``` php
$endereco = $correios->endereco('70150900');

print_r($endereco);
```

Que resulta neste output:

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

Chave              | Valor
-------------------|------
`logradouro`       | Aqui você encontra o nome da rua, avenida, praça, etc.
`logradouro_extra` | Eventualmente algum CEP vai trazer informações extras com o lado (ímpar ou par) a que ele corresponde naquele logradouro ou qual a faixa de numeração (de 1001 a 2000).
`bairro`           | O bairro deste CEP.
`cidade`           | A cidade.
`uf`               | A sigla do estado.
`cep`              | A repetição do CEP consultado.

No caso de falha na consulta serão emitidos exceptions. Fique atento para
pegá-los quando necessário. Na dúvida, verifique o código-fonte. Ele é bem
documentado e deve te ajudar a entender cada método corretamente.

###Rastreando um pacote

Para rastrear um pacote basta utilizar o método `rastreamento`:

``` php
$eventos = $correios->rastreamento('PE024442250BR');

print_r($eventos);
```

Que resulta neste output:

```
Array
(
    [0] => Array
        (
            [data] => DateTime Object
                (
                    [date] => 2015-03-12 18:12:00
                    [timezone_type] => 3
                    [timezone] => UTC
                )
            [descricao] => Entrega Efetuada
            [local] => CEE JABOATAO DOS GUARARAPES
            [cidade] => Recife
            [uf] => PE
        )
    [1] => Array
        (
            [data] => DateTime Object
                (
                    [date] => 2015-03-12 09:22:00
                    [timezone_type] => 3
                    [timezone] => UTC
                )
            [descricao] => Saiu para entrega ao destinatio
            [local] => CTE RECIFE
            [cidade] => Recife
            [uf] => PE
        )
    [2] => Array
        (
            [data] => DateTime Object
                (
                    [date] => 2015-03-11 11:31:00
                    [timezone_type] => 3
                    [timezone] => UTC
                )
            [descricao] => Encaminhado
            [local] => CTE RECIFE
            [cidade] => Recife
            [uf] => PE
        )
    [3] => ...
)
```

Como você pode ver, temos um array associativo com diversos dados referentes a
cada evento relativo ao pacote sendo consultado. Cada entrada diz respeito a um
evento e possui as seguintes chaves:

Chave       | Valor
------------|------
`data`      | Um objeto do tipo [DateTime](http://php.net/DateTime) representando o horário em que o evento aconteceu.
`descricao` | A descrição do evento em questão.
`local`     | O nome do estabeleciomento dos Correios onde este evento ocorreu.
`cidade`    | Cidade onde o evento ocorreu.
`uf`        | Sigla do estado onde o evento ocorreu.

**Uma nota sobre a consulta de pacotes**: Os Correios fornecem um webservice
específico para a consulta de pacotes, porém este webservice é reservado a
clientes que possuem contrato. Sendo assim, a biblioteca realiza um
processo de "web scraping" para extrair os dados de páginas comuns de consulta.
Embora a página sendo utilizada já tenha demonstrado uma estabilidade de anos
no que diz respeito à sua estrutura, qualquer alteração no HTML desta página
pode fazer este método falhar.

###Calculando valor de frete e prazo de entrega

Para calcular o valor de uma entrega e seu prazo, basta utilizar o método
`calculaFrete`:

``` php
$calculado = $correios->calculaFrete([
    'usuario' => null,
    'senha' => null,
    'servicos' => [Correios\Correios::PAC],
    'cep_origem' => '08820400',
    'cep_destino' => '21832150',
    'peso' => 0.3,
    'formato' => Correios\Correios::CAIXA,
    'comprimento' => 16,
    'altura' => 2,
    'largura' => 11,
    'diametro' => 5,
    'mao_propria' => false,
    'valor_declarado' => 0,
    'aviso_recebimento' => false
]);

print_r($calculado);
```

Diferente dos outros métodos, este método recebe um array com um bocado
de opções para que o cálculo seja realizado. Vamos dar uma olhada no que eles
são:

Chave               | Obrigatório? | Tipo   | Valor
--------------------|--------------|--------|------
`usuario`           | Não          | string | Se você já preencheu o seu usuário na hora de criar a instância da biblioteca, não há necessidade de definir o usuário aqui novamente. Se você não tem um usuário também não é obrigado a definir um. O usuário e senha são necessários apenas para realizar o cálculo de envios especiais como e-Sedex, PAC com contrato, etc.
`senha`             | Não          | string | Veja descrição acima.
`servicos`          | Sim          | array  | Aqui você pode fornecer os códigos de serviços que você deseja consultar num array. A biblioteca fornece constantes que podem te ajudar a escolher quais serviços você deseja consultar. Veja aqui a [tabela de serviços](#tabela-de-serviços). Você pode consultar por quantos serviços diferentes desejar.
`cep_origem`        | Sim          | string | CEP de origem do envio.
`cep_destino`       | Sim          | string | CEP de destino.
`peso`              | Sim          | float  | O peso do pacote em **kilos**, ou seja, para informar 500 gramas o valor deve ser `0.5` e assim por diante.
`formato`           | Sim          | int    | Qual o formato do pacote. Recebe um número de 1 a 3 onde 1 = caixa, 2 = rolo, 3 = envelope. A classe fornece algumas constantes para auxiliar na definição deste valor: `CAIXA`, `ROLO` e `ENVELOPE`.
`comprimento`       | Não          | int    | O comprimento do pacote.
`altura`            | Não          | int    | A altura do pacote.
`largura`           | Não          | int    | A largura do pacote.
`diametro`          | Não          | int    | O diametro do pacote.
`mao_propria`       | Não          | bool   | Define se o envio deve utilizar o serviço de [mão própria](http://www.correios.com.br/para-voce/correios-de-a-a-z/mao-propria-mp) ou não (altera o valor do envio).
`valor_declarado`   | Não          | float  | O valor daquilo que está sendo enviado para contratação de seguro (altera o valor do envio).
`aviso_recebimento` | Não          | bool   | Define se o envio deve utilizar o serviço de [aviso de recebimento](http://www.correios.com.br/para-voce/correios-de-a-a-z/aviso-de-recebimento-ar) ou não (altera o valor do envio).

Os valores não obrigatórios serão preenchidos por valores padrão minimamente
sãos. Fique de olho para garantir que o valor calculado esteja de acordo com as
reais condições de envio.

A chamada acima vai produzir o seguinte output:

```
Array
(
    [0] => Correios\Calculo Object
        (
            [raw:protected] => Array
                (
                    [codigo] => 41106
                    [valor] => 15.5
                    [prazo_entrega] => 11
                    [valor_sem_adicionais] => 15.5
                    [valor_mao_propria] => 0
                    [valor_aviso_recebimento] => 0
                    [valor_valor_declarado] => 0
                    [entrega_domiciliar] => 1
                    [entrega_sabado] =>
                    [erro] => 010
                    [msg_erro] => O CEP de destino está sujeito a condições especiais de entrega  pela  ECT e será realizada com o acréscimo de até 7 (sete) dias ao prazo regular.
                    [servico_descricao] => PAC
                )

            [serviceMap:protected] => Array
                (
                    //...
                )
        )
)
```

Veja que temos o retorno de um array cujos elementos são instâncias da classe
`Correios\Calculo`. Esta é uma classe simples que de dá acesso aos dados do
resultado da consulta como um array ou um objeto:

``` php
echo $calculado[0]['codigo']; //41106
echo $calculado[0]->valor; //15.5
```

Os dados disponíveis são os seguintes:

Chave                     | Tipo   | Descrição
--------------------------|--------|----------
`codigo`                  | string | Código do serviço referente a esta consulta.
`valor`                   | float  | Valor total do envio.
`prazo_entrega`           | int    | Número de dias para entrega.
`valor_sem_adicionais`    | float  | Valor do envio sem os serviços adicionais.
`valor_mao_propria`       | float  | Valor do serviço de mão própria.
`valor_aviso_recebimento` | float  | Valor do serviço de aviso de recebimento.
`valor_valor_declarado`   | float  | Valor do seguro.
`entrega_domiciliar`      | bool   | Informa se a localidade informada possui entrega domiciliária.
`entrega_sabado`          | bool   | Informa se a localidade informada possui entrega domiciliária aos sábados.
`erro`                    | string | Código de erro.
`msg_erro`                | string | Mensagem de erro.
`servico_descricao`       | string | Descrição do serviço.

####Calculando uma data de entrega

Lembra que o resultado da consulta traz um objeto? Ele tem alguns métodos
interessantes que podem te ajudar a manipular melhor os dados de retorno da
consulta de preço e prazo. Um destes métodos é o `calculaEntrega`. Com ele a
gente consegue ter uma data de estimativa de entrega. Veja:

``` php
$entrega = $calculado[0]->calculaEntrega(new DateTime('now'));

print_r($entrega);
```

Que resulta neste output:

```
DateTime Object
(
    [date] => 2015-05-18 00:00:00
    [timezone_type] => 3
    [timezone] => UTC
)
```

Veja que o método recebe um objeto `DateTime` e retorna um objeto `DateTime`
representando a data de entrega. O método vai levar em consideração se, a partir
da data de envio, a soma do prazo cai em algum dia do fim de semana, e ajusta
a data para o próximo dia útil seguinte, a não ser que a entrega caia num sábado
e o endereço de entrega possua o valor da chave `entrega_sabado` como `true`.

####Formatando valores

Você também pode retornar facilmente os valores de envio já formatados. Veja:

``` php
echo $calculado[0]->formataValor('valor');
```

Que resulta neste output:

```
R$ 15,50
```

O valor será formatado de acordo com o padrão monetário brasileiro. Se você
quiser, pode omitir o prefixo "R$" passando `false` no segundo argumento:

``` php
echo $calculado[0]->formataValor('valor', talse); //15,50
```

Você pode realizar esta formatação para qualquer chave de valor suprida pela
consulta.

####Ajuste inteligente de pacotes

Nenhum dos valores de dimensões dos pacotes é obrigatório de ser preenchido.
Desta forma a biblioteca usa padrões que fazem sentido pra maioria dos envios e
deve retornar valores adequados. Se você quiser personalizar os valores das
dimensões, a biblioteca automaticamente ajusta estes valores caso eles estejam
fora dos padrões definidos pelos Correios. Existem diversas regras que definem
quais são as dimensões permitidas, inclusive para cada tipo de formato
diferente.

Sendo assim, a biblioteca vai verificar qual o formato definido, as regras para
este formato e vai tentar ajustar as dimensões para que o cálculo aconteça sem
errors.

Se você desejar que a biblioteca não faça este ajuste automaticamente, basta chamar
o método da seguinte forma:

``` php
$config = []; //a configuração de sua chamada aqui...
$calculado = $correios->calculaFrete($config, false);
```

####Tabela de serviços

Aqui estão alguns dos códigos e suas respectivas descrições para consulta:

Código | Descrição
-------|----------
85480  | Aerograma
10014  | Carta Registrada
10030  | Carta Simples
16012  | Cartão Postal
81019  | e-SEDEX
20010  | Impresso
14036  | Mala Direta Postal Domiciliar
14010  | Mala Direta Postal Não Urgente
14028  | Mala Direta Postal Urgente
44105  | Malote
41106  | PAC
41300  | PAC Grandes Formatos
41262  | PAC Pagamento na Entrega
43010  | Reembolso Postal
40010  | SEDEX
40215  | SEDEX 10
40169  | SEDEX 12
40290  | SEDEX HOJE
40819  | SEDEX Pagamento na Entrega

###Usando um http adapter diferente

Por padrão a biblioteca irá usar o [Guzzle](https://packagist.org/packages/guzzlehttp/guzzle)
para realizar as requisições http aos endpoints dos webservices e páginas usados
pela biblioteca. Se você quiser, poderá utilizar outro pacote no lugar do
`Guzzle` fornecendo um novo objeto na hora de instanciar a classe dos Correios.

Para isto você deve criar uma classe intermediária que implemente a interface
`Correios\Adapters\AdapterInterface` que possui apenas dois métodos: `get` e
`post`.

Ambos os métodos devem retornar uma `string` representando o corpo da resposta
da requisição e qualquer erro no processo de requisição deve ser indicado
através da utilização de exceptions.

Veja uma implementação exemplo
[aqui](https://github.com/eberfreitas/correios/blob/master/src/Adapters/GuzzleAdapter.php).

##Créditos

* Criado por [Éber F. Dias](https://github.com/eberfreitas)
* Com o auxilio de [Tiago Correa](https://github.com/tiagocorrea)
* Patrocinado por [Tanlup](https://github.com/tanlup/)