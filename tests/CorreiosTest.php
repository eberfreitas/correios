<?php

namespace Eberfreitas\Correios\Test;

use Eberfreitas\Correios\Correios;

class ExampleTest extends \PHPUnit_Framework_TestCase
{

    public $correios;

    public function setUp()
    {
        $this->correios = new Correios();
    }

    public function tearDown()
    {
        $this->correios = null;
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('\Eberfreitas\Correios\Correios', $this->correios);
    }

    public function testAjustaPacote()
    {
        $params = [
            'nCdFormato' => 1,
            'nVlDiametro' => 0,
            'nVlComprimento' => 16,
            'nVlLargura' => 11,
            'nVlAltura' => 2
        ];

        $result = $this->correios->ajustaPacote($params);

        $this->assertEquals($result, $params);
    }
}
