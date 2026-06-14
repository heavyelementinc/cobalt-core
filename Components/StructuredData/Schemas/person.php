<?php

use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EmailAddressType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\WeakEnumType;

return [
    'additionalName' => new StringType(),
    'birthDate' => new DateType(),
    'email' => new EmailAddressType(),
    'gender' => [
        new WeakEnumType(),
        'valid' => [
            'Female' => 'Female',
            'Male' => 'Male',
        ]
    ],
    'givenName' => new StringType(),
    // 'hasCertification' => 
    // 'hasCredential' =>
    // 'height'
    'jobTitle' => [
        new StringType
    ],
];