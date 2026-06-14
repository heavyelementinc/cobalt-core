<?php

use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\WeakEnumType;

return [
    'addressCountry' => [
        new WeakEnumType(),
        'label' => 'Country',
        'valid' => [
            'US' => 'United States'
        ]
    ],
    'addressRegion' => [
        new WeakEnumType(),
        'label' => 'State/Region',
        'valid' => function () {
            return [

            ];
        }
    ],

    'postOfficeBoxNumber' => [
        new StringType(),
        'label' => 'P.O. Box',
    ],

    'streetAddress' => [
        new StringType(),
        'label' => "Street Address"
    ],
    'extendedAddress' => [
        new StringType(),
        'label' => 'Extended'
    ],

    'addressLocality' => [
        new StringType(),
        'label' => 'City/Town',
    ],
    'postalCode' => [
        new StringType(),
        'label' => "Postal Code"
    ],
    
];