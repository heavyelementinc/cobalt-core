<?php

namespace Cobalt\DataModel\Directives\Images;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractArrayDirective;
use Cobalt\JobQueue\Interfaces\BatchItem;
use Override;

#[Attribute()]
class Thumbnail extends AbstractArrayDirective {

    function __construct(array|string $array = [640, 640], public string $suffix = "t.%s") {
        return parent::__construct($array);
    }

    function getBatchItem(string $method):BatchItem {
        return new BatchItem($this->generic, $method, [...array_values($this->getValue()), $this->suffix]);
    }
}