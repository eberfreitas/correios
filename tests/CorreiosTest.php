<?php

namespace Correios\Test;

use Correios\Correios;

class CorreiosTest extends \PHPUnit_Framework_TestCase
{

    public $correios;

    protected function runProtectedMethod($obj, $method, $args = [])
    {
        $method = new \ReflectionMethod(get_class($obj), $method);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

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
        $this->assertInstanceOf('\Correios\Correios', $this->correios);
    }

    public function testAjustaPacote()
    {
        $params = [
            'nVlPeso' => 0.3,
            'nCdFormato' => 1,
            'nVlDiametro' => 0,
            'nVlComprimento' => 16,
            'nVlLargura' => 11,
            'nVlAltura' => 2
        ];

        $result = $this->runProtectedMethod($this->correios, 'ajustaPacote', [$params]);
        $this->assertEquals($result, $params);

        $params = [
            'nVlPeso' => 200,
            'nCdFormato' => 1,
            'nVlDiametro' => 200,
            'nVlComprimento' => 200,
            'nVlLargura' => 200,
            'nVlAltura' => 200
        ];

        $result = $this->runProtectedMethod($this->correios, 'ajustaPacote', [$params]);

        $expected = [
            'nVlPeso' => 30,
            'nCdFormato' => 1,
            'nVlDiametro' => 0,
            'nVlComprimento' => 66,
            'nVlLargura' => 66,
            'nVlAltura' => 66
        ];

        $this->assertEquals($result, $expected);

        $params = [
            'nVlPeso' => 1,
            'nCdFormato' => 2,
            'nVlDiametro' => 5,
            'nVlComprimento' => 18,
            'nVlLargura' => 0,
            'nVlAltura' => 0
        ];

        $result = $this->runProtectedMethod($this->correios, 'ajustaPacote', [$params]);
        $this->assertEquals($result, $params);

        $params = [
            'nVlPeso' => 0.150,
            'nCdFormato' => 2,
            'nVlDiametro' => 200,
            'nVlComprimento' => 200,
            'nVlLargura' => 200,
            'nVlAltura' => 200
        ];

        $result = $this->runProtectedMethod($this->correios, 'ajustaPacote', [$params]);

        $expected = [
            'nVlPeso' => 0.3,
            'nCdFormato' => 2,
            'nVlDiametro' => 62,
            'nVlComprimento' => 76,
            'nVlLargura' => 0,
            'nVlAltura' => 0
        ];

        $this->assertEquals($result, $expected);
    }
}
