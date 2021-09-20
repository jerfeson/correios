<?php

namespace Jerfeson\Correios;

use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use GuzzleHttp\Client;
use Jerfeson\Correios\Service\Address;
use Jerfeson\Correios\Service\Freight;

/**
 * Class Correios.
 *
 * @author  Jerfeson Guerreiro <jerfeson_guerreiro@hotmail.com>
 *
 * @since   1.0.0
 *
 * @version 1.0.0
 */
class Correios
{
    /**
     * @var Container
     */
    private Container $container;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);

        $builder->addDefinitions([
            Client::class => new Client(),
        ]);

        $this->container = $builder->build();
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     *
     * @return Address
     */
    public function address(): Address
    {
        return $this->container->get(Address::class);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     *
     * @return Freight
     */
    public function freight(): Freight
    {
        return $this->container->get(Freight::class);
    }
}
