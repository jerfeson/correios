<?php

use Codeception\Test\Unit;
use DI\DependencyException;
use DI\NotFoundException;
use GuzzleHttp\Exception\GuzzleException;
use Jerfeson\Correios\Correios;

/**
 * Class AddressTest.
 *
 * @author  Jerfeson Guerreiro <jerfeson@codeis.com.br>
 *
 * @since   1.0.0
 *
 * @version 1.0.0
 *
 * @internal
 * @coversNothing
 */
class AddressTest extends Unit
{
    /**
     * @var Correios
     */
    private Correios $client;

    /**
     * @throws GuzzleException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testZipCodeNotFound()
    {
        $this->assertEquals(['error' => 'CEP não encontrado'], $this->client->address()->find('99999-999'));
    }

    /**
     * @throws GuzzleException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testAddressFound()
    {
        $address = [
            'zipcode' => '73100-020',
            'street' => 'Rua 00',
            'complement' => [0 => []],
            'district' => 'Núcleo Rural Lago Oeste (Sobradinho)',
            'city' => 'Brasília',
            'uf' => 'DF',
        ];

        $this->assertEquals($address, $this->client->address()->find('73100-020'));
    }

    protected function _before()
    {
        $this->client = new Correios();
    }
}
