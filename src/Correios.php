<?php

namespace Eberfreitas\Correios;

class Correios
{

    protected $httpAdapter;

    public function __construct(array $options = []) {
        $defaultOptions = [
            'http_adapter' => 'Guzzle'
        ];

        $options += $defaultOptions;
    }
}