<?php

use Codeception\Test\Unit;
use DI\DependencyException;
use DI\NotFoundException;
use Jerfeson\Correios\Correios;
use Jerfeson\Correios\Enum\FreightType;
use Jerfeson\Correios\Enum\PackageType;

/**
 * Class FreightTest.
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
class FreightTest extends Unit
{
    /**
     * @var Correios
     */
    private Correios $client;

    /**
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function testSetOrigin()
    {
        $this->assertInstanceOf($this->client->freight()::class, $this->client->freight()->origin('99999-999'));
        $this->assertPayloadHas('sCepOrigem', '99999999');
    }

    /**
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function testSetDestination()
    {
        $this->assertInstanceOf($this->client->freight()::class, $this->client->freight()->destination('99999-999'));
        $this->assertPayloadHas('sCepDestino', '99999999');
    }

    /**
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function testSetServices()
    {
        $sedex = FreightType::SEDEX;

        $this->client->freight()->services($sedex);
        $this->assertPayloadHas('nCdServico', $sedex);

        $pac = FreightType::PAC;
        $this->client->freight()->services($pac);
        $this->assertPayloadHas('nCdServico', $pac, FreightType::PAC);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testPayloadWidth()
    {
        $this->client->freight()->item(1, 10, 10, 10);
        $this->client->freight()->item(2.5, 10, 10, 10);
        $this->client->freight()->item(2, 10, 10, 10);

        $this->assertPayloadHas('nVlLargura', 2.5);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testPayloadHeight()
    {
        $this->client->freight()->item(10, 1, 10, 10)
            ->item(10, 2.5, 10, 10)
            ->item(10, 2, 10, 10);

        $this->assertPayloadHas('nVlAltura', 5.5);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testPayloadLength()
    {
        $this->client->freight()->item(10, 10, 1, 10)
            ->item(10, 10, 2.5, 10)
            ->item(10, 10, 2, 10);

        $this->assertPayloadHas('nVlComprimento', 2.5);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testPayloadWeight()
    {
        $this->client->freight()->item(10, 10, 10, 1)
            ->item(10, 10, 10, 2.5)
            ->item(10, 10, 10, 2);

        $this->assertPayloadHas('nVlPeso', 5.5);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testPayloadWeightWithVolume()
    {
        $this->client->freight()->item(50, 50, 50, 1)
            ->item(50, 50, 50, 2.5)
            ->item(50, 50, 50, 2);

        $this->assertPayloadHas('nVlPeso', 62.5);
    }

    /**
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function testSetCredentials()
    {
        $code = '08082650';
        $password = 'n5f9t8';

        $this->assertInstanceOf($this->client->freight()::class, $this->client->freight()->credentials($code, $password));
        $this->assertPayloadHas('nCdEmpresa', $code)
            ->assertPayloadHas('sDsSenha', $password);
    }

    /**
     * @dataProvider packageFormatProvider
     *
     * @param mixed $format
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testSetPackageFormat(mixed $format)
    {
        $this->assertInstanceOf($this->client->freight()::class, $this->client->freight()->package($format));
        $this->assertPayloadHas('nCdFormato', $format);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testSetOwnHand()
    {
        $this->assertInstanceOf($this->client->freight()::class, $this->client->freight()->useOwnHand(false));
        $this->assertPayloadHas('sCdMaoPropria', 'N');

        $this->client->freight()->useOwnHand(true);
        $this->assertPayloadHas('sCdMaoPropria', 'S');
    }

    /**
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function testSetDeclaredValue()
    {
        $value = 10.38;

        $this->assertInstanceOf($this->client->freight()::class, $this->client->freight()->declaredValue($value));
        $this->assertPayloadHas('nVlValorDeclarado', $value);
    }

    /**
     * Provide a list of all of the packages types.
     *
     * @return array
     */
    public function packageFormatProvider(): array
    {
        return [
            [PackageType::BOX],
            [PackageType::ROLL],
            [PackageType::ENVELOPE],
        ];
    }

    protected function _before()
    {
        $this->client = new Correios();
    }

    /**
     * Asserts payload has a given key and value.
     *
     * @param int   $key
     * @param mixed $value
     * @param mixed $service
     *
     * @return FreightTest
     * @throws NotFoundException
     *
     * @throws DependencyException
     */
    protected function assertPayloadHas($key, $value = null, $service = FreightType::SEDEX): static
    {
        $currentArray = $this->client->freight()->payload($service);
        if (is_null($value)) {
            $this->assertArrayHasKey($key, $currentArray);

            return $this;
        }

        $this->assertArrayHasKey($key, $currentArray);
        $this->assertSame($value, $currentArray[$key]);

        return $this;
    }
}
