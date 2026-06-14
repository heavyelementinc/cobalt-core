<?php

use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\URLType;

return [
    // 'additionalProperty'
    'address' => [
        new ModelType(),
        'schema' => include __DIR__ . "/postal_address.php"
    ],
    'aggregateRating' => [
        new ModelType(),
        'schema' => include __DIR__ . "/aggregate_rating.php"
    ],
    'hasMap' => [
        new URLType(),
        'label' => 'Map link',
        'description' => 'Provide a link to your Google Maps location'
    ],
    'openingHoursSpecification' => [
        new ModelType(),
        'schema' => include __DIR__ . "/opening_hours_specification.php"
    ],
];