<?php

use Codeception\Test\Unit;
use Jerfeson\Correios\Correios;
use Jerfeson\Correios\Enum\FreightType;
use DI\DependencyException;
use DI\NotFoundException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class CalculateFreightTest.
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
class CalculateFreightTest extends Unit
{
    /**
     * @var Correios
     */
    private Correios $client;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws GuzzleException
     */
    public function testInvalidServiceError()
    {
        $this->client->freight()
            ->origin('01001-000')
            ->destination('73100-020')
            ->services('99999')
            ->item(16, 16, 16, .3, 1)
        ;

        $expected = [
            [
                'name' => null,
                'code' => '99999',
                'price' => 0.0,
                'deadline' => 0,
                'error' => [
                    'code' => '-888',
                    'message' => 'Para este serviço só está disponível o cálculo do PRAZO.',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->client->freight()->calculate());
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws GuzzleException
     */
    public function testWithSingleService()
    {
        $this->client->freight()
            ->origin('01001-000')
            ->destination('73100-020')
            ->services(FreightType::SEDEX)
            ->item(16, 16, 16, .3, 1)
        ;

        $expected = [
            [
                'name' => 'Sedex',
                'code' => FreightType::SEDEX,
                'price' => 35.1,
                'deadline' => 4,
                'error' => [],
            ],
        ];

        $this->assertEquals($expected, $this->client->freight()->calculate());
    }

    protected function _before()
    {
        $this->client = new Correios();
    }
}
