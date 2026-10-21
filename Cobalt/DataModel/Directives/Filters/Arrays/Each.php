<?php

namespace Cobalt\DataModel\Directives\Filters\Arrays;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractMixedDirective;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Types\Generic;
use Override;
use TypeError;

#[Attribute()]
class Each extends AbstractMixedDirective {
    protected string $name = "each";

    function __construct(Generic|string $generic, bool $isMethod = false) {
        if($generic instanceof Generic == false && $this->isStringIsGeneric($generic) == false) {
            throw new TypeError("`$generic` is not an instance of ".Generic::class);
        }
        parent::__construct($generic, $isMethod);
    }

    function isStringIsGeneric(string $generic):bool {
        return is_a($generic, Generic::class, true);
    }
}