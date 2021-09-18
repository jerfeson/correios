<?php

namespace Jerfeson\Correios\Enum;

/**
 * Class ServiceUrl.
 *
 * @author  Jerfeson Guerreiro <jerfeson_guerreiro@hotmail.com>
 *
 * @since   1.0.0
 *
 * @version 1.0.0
 */
final class ServiceUrl
{
    /**
     * URL of Correios SIGEP webservice.
     */
    public const SIGEP = 'https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente';

    /**
     * URL of the Correios webservice to calculate prices and terms.
     */
    public const CALC_PRICE_DEADLINE = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo';
}
