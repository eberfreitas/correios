<?php

namespace Correios;

/**
 * Correios
 * Um pacote para interagir com serviços dos Correios
 */
class Correios
{

    const PAC = 41106;
    const SEDEX = 40010;
    const SEDEXACOBRAR = 40045;
    const SEDEX10 = 40215;
    const SEDEXHOJE = 40290;
    const ESEDEX = 81019;

    const CAIXA = 1;
    const ROLO = 2;
    const ENVELOPE = 3;

    /** @var object Objeto que vamos utilizar pra realizar chamadas aos endpoints. */
    public $httpAdapter;

    /** @var string Guarda a informação de usuário dos Correios, se houver. */
    public $usuario;

    /** @var string Guarda senha do usuário dos Correios, se houver. */
    public $senha;

    /** @var array Lista de endpoints que serão utilizados para realizar consultas diversas. */
    protected $endpoints = [
        'calculo' => 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx',
        'cep' => 'http://m.correios.com.br/movel/buscaCepConfirma.do',
        'rastreamento' => 'http://websro.correios.com.br/sro_bin/txect01%24.QueryList'
    ];

    /**
     * Construtor da classe.
     *
     * @param array $options Aqui você pode injetar um objeto que será usando
     *     como `$httpAdapter`, passar seu nome de usuário e senha. Para usar
     *     outro adaptador para realizar as chamadas aos endpoints, veja a
     *     documentação da lib.
     *
     * @return void
     */
    public function __construct(array $options = [])
    {
        $defaultOptions = [
            'http_adapter' => null,
            'usuario' => null,
            'senha' => null
        ];

        $options += $defaultOptions;

        if (is_null($options['http_adapter'])) {
            $this->httpAdapter = new Adapters\GuzzleAdapter();
        } else {
            $this->httpAdapter = $options['http_adapter'];
            //@todo: check if the custom adapter implements the adapter interface
        }

        $this->usuario = is_null($options['usuario']) ? null : $options['usuario'];
        $this->senha = is_null($options['senha']) ? null : $options['senha'];
    }

    /**
     * Retorna os dados de rastreio de um pacote.
     *
     * @param string $code    Um código de rastreio válido.
     * @param array  $options Possíveis opções que você queira passar ao
     *     adapter que estiver sendo utilizado para realizar as requisições ao
     *     webservice.
     *
     * @throws \InvalidArgumentException Se o CEP tiver valor inválido.
     * @throws \RuntimeException         Se nenhum endereço for encontrado.
     *
     * @return array
     */
    public function rastreamento($code, array $options = [])
    {
        $code = strtoupper($code);

        if (preg_match('/^[A-Z]{2}[0-9]{9}[A-Z]{2}$/', $code) !== 1) {
            throw new \InvalidArgumentException('Use um código de rastreio válido!');
        }

        $body = [
            'P_LINGUA' => '001',
            'P_TIPO' => '001',
            'P_COD_UNI' => $code
        ];

        $html = $this->httpAdapter->post($this->endpoints['rastreamento'], $body, $options);
        $tableStart = strpos($html, '<table');

        if ($tableStart === false) {
            throw new \RuntimeException('Não foi possível carregar os dados de rastreio!');
        }

        $tableEnd = strpos($html, '/TABLE>') + 7; //mais o comprimento "/TABLE>"
        $tableLength = $tableEnd - $tableStart;
        $table = substr($html, $tableStart, $tableLength);
        $html = '<html><body>' . $table . '</body></html>';
        $html = \pQuery::parseStr($html);
        $tableCells = $html->query('table td');
        $cellContents = [];

        foreach ($tableCells as $cell) {
            $cellContents[] = $cell->getPlainText();
        }

        $cellContents = array_slice($cellContents, 3); // cabeçalhos, não precisamos deles...
        $raw = $data = [];
        $eventIndex = null;
        $i = 0;

        foreach ($cellContents as $content) {
            /**
             * Cada vez que encontramos uma data quer dizer que estamos em um
             * novo evento. Usamos o timestamp como chave pra separar os eventos
             * em um array e organizar os dados.
             */
            if (preg_match('#^[0-3]\d/[0-1]\d/(19|20)\d\d [0-2]\d:[0-5]\d$#', $content) != false) {
                $date = \DateTime::createFromFormat('d/m/Y H:i', $content);
                $eventIndex = $date->getTimestamp();
                $raw[$eventIndex] = [$date];
            } else {
                $raw[$eventIndex][] = $content;
            }
        }

        ksort($raw);
        $lastPlace = null;

        foreach ($raw as $info) {
            $parts = explode(' - ', $info[1]);

            if (count($parts) > 1) {
                $lastPlace = $parts[0];
                $address = $parts[1];
            } else {
                $address = $parts[0];
            }

            list($city, $uf) = explode('/', $address);

            $data[$i] = [];
            $data[$i]['data'] = $info[0];
            $data[$i]['descricao'] = $info[2];
            $data[$i]['local'] = trim($lastPlace);
            $data[$i]['cidade'] = trim($city);
            $data[$i]['uf'] = trim($uf);

            $i++;
        }

        return array_reverse($data);
    }

    /**
     * Pega o endereço a partir de um determinado CEP.
     *
     * @param string $cep     Qualquer CEP válido.
     * @param array  $options Possíveis opções que você queira passar ao
     *     adapter que estiver sendo utilizado para realizar as requisições ao
     *     webservice.
     *
     * @throws \InvalidArgumentException Se o CEP tiver valor inválido.
     * @throws \RuntimeException         Se nenhum endereço for encontrado.
     *
     * @return array
     */
    public function endereco($cep, array $options = [])
    {
        $cep = str_replace('-', '', $cep);

        if (strlen($cep) !== 8 || !is_numeric($cep)) {
            throw new \InvalidArgumentException('Use um valor de CEP válido!');
        }

        $body = [
            'cepEntrada' => $cep,
            'tipoCep' => '',
            'cepTemp' => '',
            'metodo' => 'buscarCep'
        ];

        $html = $this->httpAdapter->post($this->endpoints['cep'], $body, $options);
        $html = \pQuery::parseStr($html);
        $parts = $html->query('span.respostadestaque');
        $addressParts = [];
        $address = [];

        foreach ($parts as $p) {
            $addressParts[] = $p->getPlainText();
        }

        if (empty($addressParts)) {
            throw new \RuntimeException('Nenhum endereço encontrado com este CEP!');
        }

        $partsCount = count($addressParts);

        switch ($partsCount) {
            case 2:
                list($cidade, $estado) = explode('/', $addressParts[0]);

                $address = [
                    'logradouro' => null,
                    'logradouro_extra' => null,
                    'bairro' => null,
                    'cidade' => utf8_encode(trim($cidade)),
                    'uf' => utf8_encode(trim($estado)),
                    'cep' => utf8_encode(trim($addressParts[1]))
                ];

                break;
            default:
                list($cidade, $estado) = explode('/', $addressParts[2]);

                $address = [
                    'logradouro' => utf8_encode(trim($addressParts[0])),
                    'logradouro_extra' => null,
                    'bairro' => utf8_encode(trim($addressParts[1])),
                    'cidade' => utf8_encode(trim($cidade)),
                    'uf' => utf8_encode(trim($estado)),
                    'cep' => utf8_encode(trim($addressParts[3]))
                ];

                if (strpos($address['logradouro'], ' - ') !== false) {
                    $extra = explode(' - ', $address['logradouro']);
                    $street = array_shift($extra);
                    $extra = implode(' - ', $extra);
                    $address['logradouro'] = trim($street);
                    $address['logradouro_extra'] = trim($extra);
                }

                break;
        }

        return $address;
    }

    /**
     * Use este método para calcular o frete e prazo de entrega de um CEP para
     * outro CEP.
     *
     * @link http://bit.ly/1jwSH9i Documentação do webservice dos Correios.
     *
     * @param array   $config  Recebe um array com diversos valores que vão
     *     definir como o frete será calculado. Consultar a variável
     *     `$defaultConfig` para saber quais são as chaves que devem ser
     *     preenchidas e que tipo de valor elas aceitam.
     * @param boolean $fix     Se verdadeiro, este método irá corrigir qualquer
     *     irregularidade referente a tamanhos de pacotes, peso, etc., fazendo
     *     com que o valor do frete seja efetivamente calculado apesar da
     *     informação de valores errados.
     * @param array   $options Possíveis opções que você queira passar ao
     *     adapter que estiver sendo utilizado para realizar as requisições ao
     *     webservice.
     *
     * @return array
     */
    public function calculaFrete(array $config = [], $fix = true, array $options = [])
    {
        $defaultConfig = [
            'usuario' => null,
            'senha' => null,
            'servicos' => [self::PAC, self::SEDEX],
            'cep_origem' => '',
            'cep_destino' => '',
            'peso' => 0.3,
            'formato' => self::CAIXA,
            'comprimento' => 16,
            'altura' => 2,
            'largura' => 11,
            'diametro' => 5,
            'mao_propria' => false,
            'valor_declarado' => 0,
            'aviso_recebimento' => false
        ];

        $config += $defaultConfig;
        $params = $this->preparaParametrosCalculo($config);

        if ($fix) {
            $params = $this->ajustaPacote($params);
        }

        $xml = $this->httpAdapter->get($this->endpoints['calculo'], $params, $options);
        $data = $this->xmlToArray($xml);
        $return = [];

        foreach ($data['cServico'] as $d) {
            $return[] = new Calculo($d);
        }

        return $return;
    }

    /**
     * Este método recebe as opções do método de cálculo de frete e os
     * transforma em parâmetros que poderão ser usados diretamente na chamada
     * http ao endpoint de consulta do frete.
     *
     * @param array $options As opções vindas do método de cálculo de frete.
     *
     * @return array Parâmetros devidamente formatados.
     */
    protected function preparaParametrosCalculo(array $options = [])
    {
        $paramsMap = [
            'usuario' => 'nCdEmpresa',
            'senha' => 'sDsSenha',
            'servicos' => 'nCdServico',
            'cep_origem' => 'sCepOrigem',
            'cep_destino' => 'sCepDestino',
            'peso' => 'nVlPeso',
            'formato' => 'nCdFormato',
            'comprimento' => 'nVlComprimento',
            'altura' => 'nVlAltura',
            'largura' => 'nVlLargura',
            'diametro' => 'nVlDiametro',
            'mao_propria' => 'sCdMaoPropria',
            'valor_declarado' => 'nVlValorDeclarado',
            'aviso_recebimento' => 'sCdAvisoRecebimento'
        ];

        $params = [];

        foreach ($paramsMap as $opt => $param) {
            $params[$param] = $options[$opt];
        }

        $params['sCdMaoPropria'] = $params['sCdMaoPropria'] === true ? 'S' : 'N';
        $params['sCdAvisoRecebimento'] = $params['sCdAvisoRecebimento'] === true ? 'S' : 'N';

        if (is_null($params['nCdEmpresa']) && !is_null($this->usuario)) {
            $params['nCdEmpresa'] = $this->usuario;
        }

        if (is_null($params['sDsSenha']) && !is_null($this->senha)) {
            $params['sDsSenha'] = $this->senha;
        }

        $params['nCdServico'] = implode(',', (array)$params['nCdServico']);
        $params['StrRetorno'] = 'xml';
        $params['nIndicaCalculo'] = 3; // preço e prazo

        return $params;
    }

    /**
     * Método que ajusta as dimensções de um determinado pacote para que o
     * cálculo aconteça corretamente.
     *
     * @link http://bit.ly/1JVa7Ls Especificações de tamanho.
     *
     * @param array $params Os parametros do cálculo do frete.
     *
     * @return array
     */
    protected function ajustaPacote(array $params = [])
    {
        if ($params['nVlPeso'] < 0.3) {
            $params['nVlPeso'] = 0.3;
        } elseif ($params['nVlPeso'] > 1 && $params['nCdFormato'] === self::ENVELOPE) {
            $params['nVlPeso'] = 1;
        } elseif ($params['nVlPeso'] > 30) {
            $params['nVlPeso'] = 30;
        }

        switch ($params['nCdFormato']) {
            case self::CAIXA:
                $params['nVlDiametro'] = 0;

                if ($params['nVlComprimento'] < 16) {
                    $params['nVlComprimento'] = 16;
                    return $this->ajustaPacote($params);
                } elseif ($params['nVlComprimento'] > 105) {
                    $params['nVlComprimento'] = 105;
                    return $this->ajustaPacote($params);
                }

                if ($params['nVlLargura'] < 11) {
                    $params['nVlLargura'] = 11;
                    return $this->ajustaPacote($params);
                } elseif ($params['nVlLargura'] > 105) {
                    $params['nVlLargura'] = 105;
                    return $this->ajustaPacote($params);
                }

                if ($params['nVlAltura'] < 2) {
                    $params['nVlAltura'] = 2;
                    return $this->ajustaPacote($params);
                } elseif ($params['nVlAltura'] > 105) {
                    $params['nVlAltura'] = 105;
                    return $this->ajustaPacote($params);
                }

                $sum = $params['nVlComprimento'] + $params['nVlLargura'] + $params['nVlAltura'];

                if ($sum > 200) {
                    while ($sum > 200) {
                        if ($params['nVlComprimento'] > 16) {
                            $params['nVlComprimento']--;
                        }

                        if ($params['nVlLargura'] > 11) {
                            $params['nVlLargura']--;
                        }

                        if ($params['nVlAltura'] > 2) {
                            $params['nVlAltura']--;
                        }

                        $sum = $params['nVlComprimento'] + $params['nVlLargura'] + $params['nVlAltura'];
                    }

                    return $this->ajustaPacote($params);
                }

                break;
            case self::ROLO:
                $params['nVlAltura'] = 0;
                $params['nVlLargura'] = 0;

                if ($params['nVlComprimento'] < 18) {
                    $params['nVlComprimento'] = 18;
                    return $this->ajustaPacote($params);
                } elseif ($params['nVlComprimento'] > 105) {
                    $params['nVlComprimento'] = 105;
                    return $this->ajustaPacote($params);
                }

                if ($params['nVlDiametro'] < 5) {
                    $params['nVlDiametro'] = 5;
                    return $this->ajustaPacote($params);
                } elseif ($params['nVlDiametro'] > 91) {
                    $params['nVlDiametro'] = 91;
                    return $this->ajustaPacote($params);
                }

                $sum = $params['nVlComprimento'] + (2 * $params['nVlDiametro']);

                if ($sum > 200) {
                    while ($sum > 200) {
                        if ($params['nVlComprimento'] > 18) {
                            $params['nVlComprimento']--;
                        }

                        if ($params['nVlDiametro'] > 5) {
                            $params['nVlDiametro']--;
                        }

                        $sum = $params['nVlComprimento'] + (2 * $params['nVlDiametro']);
                    }

                    return $this->ajustaPacote($params);
                }

                break;
            case self::ENVELOPE:
                $params['nVlAltura'] = 0;
                $params['nVlDiametro'] = 0;

                if ($params['nVlComprimento'] < 16) {
                    $params['nVlComprimento'] = 16;
                    return $this->ajustaPacote($params);
                } elseif ($params['nVlComprimento'] > 60) {
                    $params['nVlComprimento'] = 60;
                    return $this->ajustaPacote($params);
                }

                if ($params['nVlLargura'] < 11) {
                    $params['nVlLargura'] = 11;
                    return $this->ajustaPacote($params);
                } elseif ($params['nVlLargura'] > 60) {
                    $params['nVlLargura'] = 60;
                    return $this->ajustaPacote($params);
                }

                break;
        }

        return $params;
    }

    /**
     * Método simples pra transformar um xml em array
     *
     * @param string $xml O string do XML sendo manipulado.
     *
     * @return array
     */
    protected function xmlToArray($xml)
    {
        $xml = simplexml_load_string($xml, null, LIBXML_NOCDATA);
        return json_decode(json_encode($xml), true);
    }
}
