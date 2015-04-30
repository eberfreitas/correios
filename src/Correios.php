<?php

namespace Eberfreitas\Correios;

class Correios
{

    const SEDEX = 40010;
    const SEDEXACOBRAR = 40045;
    const SEDEX10 = 40215;
    const SEDEXHOJE = 40290;
    const PAC = 41106;
    const ESEDEX = 81019;

    const CAIXA = 1;
    const ROLO = 2;
    const ENVELOPE = 3;

    public $httpAdapter;

    public $usuario;

    public $senha;

    protected $endpoints = [
        'calculo' => 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx'
    ];

    public function __construct(array $options = [])
    {
        $defaultOptions = [
            'http_adapter' => null,
            'usuario' => null,
            'senha' => null
        ];

        $options += $defaultOptions;

        /*if (is_null($options['http_adapter'])) {
            $this->httpAdapter = new Adapters\GuzzleAdapter();
        } else {
            $this->httpAdapter = $options['http_adapter'];
            //@TODO: check if the custom adapter implements the adapter interface
        }*/

        $this->usuario = is_null($options['usuario']) ? null : $options['usuario'];
        $this->senha = is_null($options['senha']) ? null : $options['senha'];
    }

    public function calculaFrete(array $options = [])
    {
        $defaultOptions = [
            'usuario' => null,
            'senha' => null,
            'servicos' => [],
            'cep_origem' => '',
            'cep_destino' => '',
            'peso' => 0.3,
            'formato' => 1,
            'comprimento' => 16,
            'altura' => 2,
            'largura' => 11,
            'diametro' => 5,
            'mao_propria' => false,
            'valor_declarado' => 0,
            'aviso_recebimento' => false
        ];

        $options += $defaultOptions;

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

        $param = $this->preparaParametrosCalculo($params);
    }

    protected function preparaParametrosCalculo(array $params = [])
    {
        $params['sCdMaoPropria'] = $params['sCdMaoPropria'] === true ? 'S' : 'N';
        $params['sCdAvisoRecebimento'] = $params['sCdAvisoRecebimento'] === true ? 'S' : 'N';

        if ($params['nVlPeso'] < 0.3) {
            $params['nVlPeso'] = 0.3;
        } elseif ($params['nVlPeso'] > 1 && $params['nCdFormato'] === self::ENVELOPE) {
            $params['nVlPeso'] = 1;
        } elseif ($params['nVlPeso'] > 30) {
            $params['nVlPeso'] = 30;
        }

        if (is_null($params['nCdEmpresa']) && !is_null($this->usuario)) {
            $params['nCdEmpresa'] = $this->usuario;
        }

        if (is_null($params['sDsSenha']) && !is_null($this->senha)) {
            $params['sDsSenha'] = $this->senha;
        }

        $params['nCdServico'] = implode(',', (array)$params['nCdServico']);

        return $this->ajustaPacote($params);
    }

    /**
     * Método que ajusta as dimensções de um determinado pacote para que o
     * cálculo aconteça corretamente.
     * @link http://www2.correios.com.br/sistemas/precosprazos/Formato.cfm
     */
    protected function ajustaPacote($params)
    {
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
}
