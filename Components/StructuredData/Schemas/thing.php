<?php

use Cobalt\Model\Types\ImageArrayType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\TextType;

return [
    'additionalType' => new TextType(),
    'alternateName' => new StringType(),
    'description' => new TextType(),
    'disambiguationDescription' => new TextType(),
    'identifier' => new StringType(),
    'image' => new ImageType(),
    'name' => new StringType(),
    // 'owner' => 
];