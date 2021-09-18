<?php

namespace Jerfeson\Correios\Service;

use GuzzleHttp\Client;

/**
 * Class Service.
 *
 * @author  Jerfeson Guerreiro <jerfeson_guerreiro@hotmail.com>
 *
 * @since   1.0.0
 *
 * @version 1.0.0
 */
abstract class Service
{
    /**
     * @var Client
     */
    private Client $http;

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    /**
     * @return Client
     */
    final public function getHttp(): Client
    {
        return $this->http;
    }

    /**
     * @param Client $http
     */
    final public function setHttp(Client $http): void
    {
        $this->http = $http;
    }
}
