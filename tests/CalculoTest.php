<?php

namespace Correios\Test;

use Correios\Calculo;

class CalculoTest extends \PHPUnit_Framework_TestCase
{

    public $calculo;

    public function setUp()
    {
        $data = [];
        $data['Codigo'] = '41106';
        $data['Valor'] = '15,50';
        $data['PrazoEntrega'] = 11;
        $data['ValorSemAdicionais'] = '15,50';
        $data['ValorMaoPropria'] = '0,00';
        $data['ValorAvisoRecebimento'] = '0,00';
        $data['ValorValorDeclarado'] = '0,00';
        $data['EntregaDomiciliar'] = 'S';
        $data['EntregaSabado'] = 'S';
        $data['Erro'] = '010';
        $data['MsgErro'] = 'O CEP de destino está sujeito a condições especiais de entrega  pela  ECT e será realizada com o acréscimo de até 7 (sete) dias ao prazo regular.';

        $this->calculo = new Calculo($data);
    }

    public function tearDown()
    {
        $this->calculo = null;
    }

    public function testCalculaEntrega()
    {
        $date = new \DateTime('2015-05-07');
        $entrega = $this->calculo->calculaEntrega($date);
        $interval = $entrega->diff($date);

        $this->assertEquals('11', $interval->format('%a'));
    }
}
