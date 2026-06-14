<?php

use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\TimeType;

return [
    'opens' => [
        new TimeType(),
        'label' => 'Opens',
    ],    
    'closes' => [
        new TimeType(),
        'label' => 'Closes',
    ],
    'dayOfWeek' => [
        new EnumType(),
        'valid' => [
            'Su' => 'Sunday',
            'Mo' => 'Monday',
            'Tu' => 'Tuesday',
            'We' => 'Wednesday',
            'Th' => 'Thursday',
            'Fr' => 'Friday',
            'Sa' => 'Saturday',
        ]
    ],
    'validFrom' => [
        new DateType(),
        'label' => 'From',
    ],
    'validThrough' => [
        new DateType(),
        'label' => 'Through',
    ],
];