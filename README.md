# Correios for PHP

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg)](https://php.net/)

This library facilitates the integration with the Correios delivery services of Brazil on php

## Functionalities

- [Consult address by zip code](#find-address)
- [Calculate Prices and Deadlines](#calculate-prices-and-deadlines)

## Installation

 - Using composer

```bash
    $ composer require jerfeson/correios
```

## How to use

### Consult address by zip code

``` php
use Correios\Correios;

require 'vendor/autoload.php';

$correios = new Correios();
$repsonse = $correios->address()->find("73100‑020");

/** result 
[
    'zipcode' => '01001-000',
    'street' => 'Praça da Sé',
    'complement' => [
        'lado ímpar',
    ],
    'district' => 'Sé',
    'city' => 'São Paulo',
    'uf' => 'SP',
]

**/
```

### Calculate Prices and Deadlines

Calculate prices and terms of delivery services (Sedex, PAC and etc), with `support for multiple objects` in the same query.

``` php 
use Correios\Correios;

require 'vendor/autoload.php';

$correios = new Correios();

$response = $correios->freight()
    ->origin('01001-000')
    ->destination('73100‑020')
    ->services(FreightType::SEDEX, FreightType::PAC)
    ->item(16, 16, 16, .3, 1);
    
    
/** result
[
    0 =>
        [
            'name' => 'Sedex',
            'code' => '4014',
            'price' => 35.1,
            'deadline' => 4,
            'error' => [],
        ],
    1 =>
        [
            'name' => 'PAC',
            'code' => '4510',
            'price' => 24.8,
            'deadline' => 8,
            'error' =>[],
        ],
]
*/
```
## How to test 

``` bash
    $ composer test
```

## Roadmap

- [ ] CodeCoverage

## License

jerfeson/correios is release under the MIT license.

## Thanks

This project is based on the project in flyingluscas/correios-php feel free to contribute to this and the other project.