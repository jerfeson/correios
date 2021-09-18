<?php

namespace Jerfeson\Correios\Service;

use GuzzleHttp\Exception\GuzzleException;
use Jerfeson\Correios\Enum\ServiceUrl;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

/**
 * Class Address.
 *
 * @author  Jerfeson Guerreiro <jerfeson_guerreiro@hotmail.com>
 *
 * @since   1.0.0
 *
 * @version 1.0.0
 * +
 */
class Address extends Service
{
    /**
     * @var string
     */
    private string $zipCode;

    /**
     * @var ResponseInterface
     */
    private ResponseInterface $response;

    /**
     * @var string
     */
    private string $body;

    /**
     * @var mixed
     */
    private mixed $parsedXML;

    /**
     * @param string $zipCode
     *
     * @throws GuzzleException
     *
     * @return array
     */
    public function find(string $zipCode): array
    {
        $this->setZipCode($zipCode);
        $this->buildXMLBody();
        $this->sendWebServiceRequest();
        $this->parseXMLFromResponse();

        if ($this->hasErrorMessage()) {
            return $this->fetchErrorMessage();
        }

        return $this->payloadAddress();
    }

    /**
     * @return string
     */
    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    /**
     * Fill out request parameters.
     *
     * @return self
     */
    protected function buildXMLBody(): static
    {
        $zipcode = preg_replace('/[^0-9]/', null, $this->getZipCode());

        $xml = new SimpleXMLElement('<Envelope/>');
        $xml->addAttribute('xmlns', 'http://schemas.xmlsoap.org/soap/envelope/');
        $body = $xml->addChild('Body');
        $consulta = $body->addChild('consultaCEP');
        $consulta->addAttribute('xmlns', 'http://cliente.bean.master.sigep.bsb.correios.com.br/');
        $consulta->addChild('cep', $zipcode)->addAttribute('xmlns', '');
        $this->body = trim($xml->asXML());

        return $this;
    }

    /**
     * Sends a request to the webservice and saves the response.
     *
     * @throws GuzzleException
     *
     * @return self
     */
    protected function sendWebServiceRequest(): static
    {
        $this->response = $this->getHttp()->post(ServiceUrl::SIGEP, [
            'http_errors' => false,
            'body' => $this->body,
            'headers' => [
                'Content-Type' => 'application/xml; charset=utf-8',
                'cache-control' => 'no-cache',
            ],
        ]);

        return $this;
    }

    /**
     * Format the response body XML.
     *
     * @return self
     */
    protected function parseXMLFromResponse(): static
    {
        $xml = $this->response->getBody()->getContents();
        $parse = simplexml_load_string(str_replace([
            'soap:',
            'ns2:',
        ], null, $xml));
        $this->parsedXML = json_decode(json_encode($parse->Body), true);

        return $this;
    }

    /**
     * Checks if there is any error message in the XML returned from the request.
     *
     * @return bool
     */
    protected function hasErrorMessage(): bool
    {
        return array_key_exists('Fault', $this->parsedXML);
    }

    /**
     * Recover error message from formatted XML.
     *
     * @return array
     */
    #[ArrayShape(['error' => 'string'])]
    protected function fetchErrorMessage(): array
    {
        return [
            'error' => $this->messages($this->parsedXML['Fault']['faultstring']),
        ];
    }

    /**
     * More human error messages.
     *
     * @param string $faultString
     *
     * @return string
     */
    protected function messages(string $faultString): string
    {
        $messages = [
            'CEP INVÁLIDO' => 'CEP não encontrado',
            'CEP NAO ENCONTRADO' => 'CEP não encontrado',
        ];

        return $messages[$faultString];
    }

    /**
     * Retrieves address from reply XML.
     *
     * @return array
     */
    protected function payloadAddress(): array
    {
        $response = $this->parsedXML['consultaCEPResponse']['return'];
        $zipcode = preg_replace('/^([0-9]{5})([0-9]{3})$/', '${1}-${2}', $response['cep']);
        $complement = $this->getComplement($response);

        return [
            'zipcode' => $zipcode,
            'street' => $response['end'],
            'complement' => $complement,
            'district' => $response['bairro'],
            'city' => $response['cidade'],
            'uf' => $response['uf'],
        ];
    }

    /**
     * Returns complement of an address.
     *
     * @param array $address
     *
     * @return array
     */
    protected function getComplement(array $address): array
    {
        $complement = [];

        if (array_key_exists('complemento', $address)) {
            $complement[] = $address['complemento'];
        }

        if (array_key_exists('complemento2', $address)) {
            $complement[] = $address['complemento2'];
        }

        return $complement;
    }
}
