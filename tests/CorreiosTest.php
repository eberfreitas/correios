<?php

namespace Eberfreitas\Correios\Test;

use Eberfreitas\Correios\Correios;

class ExampleTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $correios = new Correios();
        $this->assertInstanceOf('\Eberfreitas\Correios\Correios', $correios);
    }
}