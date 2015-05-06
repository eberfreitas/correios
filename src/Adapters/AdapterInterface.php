<?php

namespace Correios\Adapters;

interface AdapterInterface
{
    /**
     * Makes GET requests
     *
     * @param string $url     O endereço da requisição.
     * @param array  $query   Array com as chaves e valores da query string.
     * @param array  $options Array com outras opções que possam ser usadas
     *     pelo adapter na hora de realizar a requisição.
     *
     * @return string
     */
    public function get($url, array $query, array $options = []);
}
