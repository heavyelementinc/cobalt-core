<?php

use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\TextType;

return [
    // 'author' => new StringType,
    'bestRating' => new NumberType(),
    'ratingExplanation' => new TextType(),
    'ratingValue' => new NumberType(),
    'reviewAspect' => new TextType(),
    'worstRating' => new NumberType(),
];