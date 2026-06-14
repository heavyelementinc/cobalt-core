<?php

use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;

return [
    'itemReviewed' => new StringType(),
    'ratingCount' => new NumberType(),
    'reviewCount' => new NumberType(),
    ...include __DIR__ . "/rating.php"
];