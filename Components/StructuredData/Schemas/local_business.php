<?php

use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\TextType;
use Cobalt\Model\Types\TimeType;
use Cobalt\Model\Types\WeakEnumType;
use Dom\Text;

return [
    'currenciesAccepted' => [
        new ArrayType(),
        'valid' => [
            'USD' => 'USD'
        ],
        'custom' => true,
        'label' => 'Currencies Accepted',
        'description' => 'Must be in currency ticker format. <a href="http://en.wikipedia.org/wiki/ISO_4217">ISO 4217 currency format</a>.'
    ],
    'floorLevel' => [
        new StringType(),
    ],
    'openingHours' => [
        new TextType(),
    ],
    'paymentAccepted' => [
        new ArrayType(),
        'valid' => [
            'Cash' => 'Cash',
            'Credit Card' => 'Credit Card',
            'Cryptocurrency' => 'Cryptocurrency',
            'Local Exchange Tradings System' => 'Local Exchange Tradings System',
        ]
    ],
    'priceRange' => [
        new NumberType(),
        'min' => 0,
        'max' => 4,
        'display' => function ($v) {
            return str_pad("", $v, "$");
        },
        'label' => 'Price Range'
    ],
    ...include __DIR__ . "/organization.php"
];