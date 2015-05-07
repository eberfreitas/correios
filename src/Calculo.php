<?php

namespace Correios;

/**
 * Classe que define um objeto para o retorno dos cálculos de frente, conferindo
 * recursos extras.
 */
class Calculo implements \ArrayAccess
{

    /** @var array Onde são guardados os dados do cálculo. */
    protected $raw = [];

    /**
     * Construtor da classe.
     *
     * @param array $data Os dados referentes ao cálculo de frete.
     *
     * @throws \InvalidArgumentException Se não for fornecido um array válido.
     *
     * @return void
     */
    public function __construct(array $data = [])
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('É necessário fornecer os dados de envio!');
        }

        $propertiesMap = [
            'Codigo' => 'codigo',
            'Valor' => 'valor',
            'PrazoEntrega' => 'prazo_entrega',
            'ValorSemAdicionais' => 'valor_sem_adicionais',
            'ValorMaoPropria' => 'valor_mao_propria',
            'ValorAvisoRecebimento' => 'valor_aviso_recebimento',
            'ValorValorDeclarado' => 'valor_valor_declarado',
            'EntregaDomiciliar' => 'entrega_domiciliar',
            'EntregaSabado' => 'entrega_sabado',
            'Erro' => 'erro',
            'MsgErro' => 'msg_erro'
        ];

        $newSet = [];

        foreach ($propertiesMap as $old => $new) {
            $newSet[$new] = $data[$old];
        }

        $fixValue = function ($value) {
            $value = str_replace(',', '', $value);
            return (int)$value / 100;
        };

        $newSet['valor'] = $fixValue($newSet['valor']);
        $newSet['valor_sem_adicionais'] = $fixValue($newSet['valor_sem_adicionais']);
        $newSet['valor_mao_propria'] = $fixValue($newSet['valor_mao_propria']);
        $newSet['valor_aviso_recebimento'] = $fixValue($newSet['valor_aviso_recebimento']);
        $newSet['valor_valor_declarado'] = $fixValue($newSet['valor_valor_declarado']);
        $newSet['entrega_domiciliar'] = $newSet['entrega_domiciliar'] === 'S' ? true : false;
        $newSet['entrega_sabado'] = $newSet['entrega_sabado'] === 'S' ? true : false;

        $this->raw = $newSet;
    }

    /**
     * Calcula a data de entrega baseado nos dados de cálculo e numa data
     * de envio específica.
     *
     * @param \DateTime $date Define a data de envio do pacote.
     *
     * @return \DateTime Objeto que representa a data de entrega estimada.
     */
    public function calculaEntrega(\DateTime $date)
    {
        $toSend = clone $date;
        $toSend->modify('+' . $this->raw['prazo_entrega'] . ' days');
        $weekDay = (int)$toSend->format('N');

        if ($weekDay === 6 && $this->raw['entrega_sabado'] === false) {
            $toSend->modify('+2 days');
        } elseif ($weekDay === 7) {
            $toSend->modify('+1 days');
        }

        return $toSend;
    }

    /**
     * Offset to set.
     *
     * @param mixed $offset Offset.
     * @param mixed $value  Value.
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->raw[] = $value;
        } else {
            $this->raw[$offset] = $value;
        }
    }

    /**
     * Whether an offset exists.
     *
     * @param mixed $offset Offset.
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->raw[$offset]);
    }

    /**
     * Offset to unset.
     *
     * @param mixed $offset Offset.
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->raw[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @param mixed $offset Offset.
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->raw[$offset]) ? $this->raw[$offset] : null;
    }
}
