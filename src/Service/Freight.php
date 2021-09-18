<?php

namespace Jerfeson\Correios\Service;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Jerfeson\Correios\Enum\FreightType;
use Jerfeson\Correios\Enum\PackageType;
use Jerfeson\Correios\Enum\ServiceUrl;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class Freight.
 *
 * @author  Jerfeson Guerreiro <jerfeson_guerreiro@hotmail.com>
 *
 * @since   1.0.0
 *
 * @version 1.0.0
 */
class Freight extends Service
{
    /**
     * Correios services.
     *
     * @see FreightType
     *
     * @var array
     */
    protected array $services = [];

    /**
     * Payload default.
     *
     * @var array
     */
    protected array $defaultPayload = [
        'nCdEmpresa' => '',
        'sDsSenha' => '',
        'nCdServico' => '',
        'sCepOrigem' => '',
        'sCepDestino' => '',
        'nCdFormato' => PackageType::BOX,
        'nVlLargura' => 0,
        'nVlAltura' => 0,
        'nVlPeso' => 0,
        'nVlComprimento' => 0,
        'nVlDiametro' => 0,
        'sCdMaoPropria' => 'N',
        'nVlValorDeclarado' => 0,
        'sCdAvisoRecebimento' => 'N',
    ];

    /**
     * Payload request.
     *
     * @var array
     */
    protected array $payload = [];

    /**
     * Objects to be transported.
     *
     * @var array
     */
    protected array $items = [];

    /**
     * Payload the request to the Correios webservice.
     *
     * @param string $service Serviço (Sedex, PAC...)
     *
     * @return array
     */
    public function payload(string $service): array
    {
        $this->payload['nCdServico'] = $service;

        if ($this->items) {
            $this->payload['nVlLargura'] = $this->width();
            $this->payload['nVlAltura'] = $this->height();
            $this->payload['nVlComprimento'] = $this->length();
            $this->payload['nVlDiametro'] = 0;
            $this->payload['nVlPeso'] = $this->useWeightOrVolume();
        }

        return array_merge($this->defaultPayload, $this->payload);
    }

    /**
     * Origin zipcode.
     *
     * @param string $zipCode
     *
     * @return self
     */
    public function origin(string $zipCode): static
    {
        $this->payload['sCepOrigem'] = preg_replace('/[^0-9]/', null, $zipCode);

        return $this;
    }

    /**
     * Destiny zipcode.
     *
     * @param string $zipCode
     *
     * @return Freight
     */
    public function destination($zipCode): static
    {
        $this->payload['sCepDestino'] = preg_replace('/[^0-9]/', null, $zipCode);

        return $this;
    }

    /**
     * Services to be calculated.
     *
     * @param int ...$services
     *
     * @return self
     */
    public function services(...$services): static
    {
        $this->services = array_unique($services);

        return $this;
    }

    /**
     * Administrative code with the ECT. The code is available in the body of the contract signed with Correios.
     *
     * Password for accessing the service, associated with your administrative code,
     * the initial password corresponds to the first 8 digits of the CNPJ informed in the contract.
     *
     * @param string $code
     * @param string $password
     *
     * @return self
     */
    public function credentials(string $code, string $password): static
    {
        $this->payload['nCdEmpresa'] = $code;
        $this->payload['sDsSenha'] = $password;

        return $this;
    }

    /**
     * Order format (box, package, roll, prism or envelope).
     *
     * @param int $format
     *
     * @return self
     */
    public function package($format): static
    {
        $this->payload['nCdFormato'] = $format;

        return $this;
    }

    /**
     * Indicate whether the order will be delivered with the additional service by hand.
     *
     * @param bool $useOwnHand
     *
     * @return Freight
     */
    public function useOwnHand($useOwnHand): static
    {
        $this->payload['sCdMaoPropria'] = (bool) $useOwnHand ? 'S' : 'N';

        return $this;
    }

    /**
     * Indicate whether the order will be delivered with the declared value additional service,
     * the desired declared value must be presented, in Brazilian real.
     *
     * @param float|int $value
     *
     * @return self
     */
    public function declaredValue(float|int $value): static
    {
        $this->payload['nVlValorDeclarado'] = floatval($value);

        return $this;
    }

    /**
     * Dimensions, weight and quantity of the item.
     *
     * @param float|int $width
     * @param float|int $height
     * @param float|int $length
     * @param float|int $weight
     * @param int       $quantity
     *
     * @return self
     */
    public function item(float|int $width, float|int $height, float|int $length, float|int $weight, $quantity = 1): static
    {
        $this->items[] = compact('width', 'height', 'length', 'weight', 'quantity');

        return $this;
    }

    /**
     * Calculates prices and terms with the Correios.
     *
     * @throws GuzzleException
     *
     * @return array
     */
    public function calculate(): array
    {
        $servicesResponses = array_map(function ($service) {
            return $this->getHttp()->get(ServiceUrl::CALC_PRICE_DEADLINE, [
                'query' => $this->payload($service),
            ]);
        }, $this->services);

        $services = array_map([
            $this,
            'fetchCorreiosService',
        ], $servicesResponses);

        return array_map([
            $this,
            'transformCorreiosService',
        ], $services);
    }

    /**
     * Calculates and returns the largest width among all items.
     *
     * @return float|int
     */
    protected function width(): float|int
    {
        return max(array_map(function ($item) {
            return $item['width'];
        }, $this->items));
    }

    /**
     * Calculates and returns the sum total of the height of all items.
     *
     * @return float|int
     */
    protected function height(): float|int
    {
        return array_sum(array_map(function ($item) {
            return $item['height'] * $item['quantity'];
        }, $this->items));
    }

    /**
     * Calculates and returns the longest length among all items.
     *
     * @return float|int
     */
    protected function length(): float|int
    {
        return max(array_map(function ($item) {
            return $item['length'];
        }, $this->items));
    }

    /**
     * Calculates and returns the sum total of the weight of all items.
     *
     * @return float|int
     */
    protected function weight(): float|int
    {
        return array_sum(array_map(function ($item) {
            return $item['weight'] * $item['quantity'];
        }, $this->items));
    }

    /**
     * Calculates the shipping volume based on the length, width and height of the items.
     *
     * @return float|int
     */
    protected function volume(): float|int
    {
        return ($this->length() * $this->width() * $this->height()) / 6000;
    }

    /**
     * Calculates what value (volume or physical weight) should be used as the shipping weight in the final requisition.
     *
     * @return float|int
     */
    protected function useWeightOrVolume(): float|int
    {
        if ($this->volume() < 10 || $this->volume() <= $this->weight()) {
            return $this->weight();
        }

        return $this->volume();
    }

    /**
     * Extracts all services returned in the Post Office response XML.
     *
     * @param Response $response
     *
     * @return array
     */
    protected function fetchCorreiosService(Response $response): array
    {
        $xml = simplexml_load_string($response->getBody()->getContents());
        $result = json_decode(json_encode($xml->Servicos));

        return get_object_vars($result->cServico);
    }

    /**
     * It transforms a Correios service into a cleaner, more readable and easier to manipulate array.
     *
     * @param array $service
     *
     * @return array
     */
    #[ArrayShape([
        'name' => 'null|string',
        'code' => 'mixed',
        'price' => 'float',
        'deadline' => 'int',
        'error' => 'array',
    ])]
    protected function transformCorreiosService(array $service): array
    {
        $error = [];

        if ($service['Erro'] != 0) {
            $error = [
                'code' => $service['Erro'],
                'message' => $service['MsgErro'],
            ];
        }

        return [
            'name' => $this->friendlyServiceName($service['Codigo']),
            'code' => $service['Codigo'],
            'price' => floatval(str_replace(',', '.', $service['Valor'])),
            'deadline' => intval($service['PrazoEntrega']),
            'error' => $error,
        ];
    }

    /**
     * Name of services (Sedex, PAC...) based on the code.
     *
     * @param string $code
     *
     * @return null|string
     */
    protected function friendlyServiceName(string $code): ?string
    {
        $id = intval($code);
        $services = [
            intval(FreightType::PAC) => 'PAC',
            intval(FreightType::PAC_CONTRATO) => 'PAC',
            intval(FreightType::PAC_CONTRATO_04812) => 'PAC',
            intval(FreightType::PAC_CONTRATO_41068) => 'PAC',
            intval(FreightType::PAC_CONTRATO_41211) => 'PAC',
            intval(FreightType::SEDEX) => 'Sedex',
            intval(FreightType::SEDEX_CONTRATO) => 'Sedex',
            intval(FreightType::SEDEX_A_COBRAR) => 'Sedex a Cobrar',
            intval(FreightType::SEDEX_10) => 'Sedex 10',
            intval(FreightType::SEDEX_HOJE) => 'Sedex Hoje',
            intval(FreightType::SEDEX_CONTRATO_04316) => 'Sedex',
            intval(FreightType::SEDEX_CONTRATO_40096) => 'Sedex',
            intval(FreightType::SEDEX_CONTRATO_40436) => 'Sedex',
            intval(FreightType::SEDEX_CONTRATO_40444) => 'Sedex',
            intval(FreightType::SEDEX_CONTRATO_40568) => 'Sedex',
        ];

        if (array_key_exists($id, $services)) {
            return $services[$id];
        }

        return null;
    }
}
