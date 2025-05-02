<?php

namespace Cobalt\Model\Directives\Interfaces;

use Cobalt\Model\Types\MixedType;

interface DirectiveInterface {
    function getReference(): MixedType;
    function setReference(MixedType $value):void;
}