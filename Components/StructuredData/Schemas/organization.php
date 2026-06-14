<?php

use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EmailAddressType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\PhoneNumberType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\TextType;
use Cobalt\Model\Types\TimeType;
use Cobalt\Model\Types\WeakEnumType;

return [
    'acceptedPaymentMethod' => [
        new ArrayType(),
        'valid' => [
            'LoanOrCredit' => 'Loan Or Credit',
            'PaymentMethod' => 'Payment Method',
            'Text' => 'Text'
        ],
    ],
    'actionableFeedbackPolicy' => [
        new ArrayType(),
        'valid' => [
            'CreativeWork' => 'Creative Work',
            'URL' => 'URL'
        ]
    ],
    'address' => [
        new ArrayType(),
        'valid' => include __DIR__ . "/postal_address.php",
    ],
    'duns' => [
        new StringType(),
        'label' => 'DUNS Number'
    ],
    'email' => [
        new EmailAddressType(),
    ],
    'founder' => [
        new ModelType,
        'schema' => include __DIR__ . "/person.php"
    ],
    'foundingDate' => new DateType(),
    // 'foundingLocation' => 
    // 'hasCertification' =>
    // 'hasCredential' => 
    //
    'keywords' => new ArrayType(),
    'niacs' => [
        new EnumType(),
        'valid' => function () {
            return include __DIR__ . "/../Enums/niacs.php";
        },
        'input_tag' => 'input-autocomplete'
    ],
    'nonProfitStatus' => new StringType(),
    'numberOfEmployees' => new NumberType(),
    'skills' => [
        new ArrayType(),
        'custom' => true,
    ],
    'slogan' => new StringType(),
    'telephone' => new PhoneNumberType(),
    ...include __DIR__ . "/place.php"
];