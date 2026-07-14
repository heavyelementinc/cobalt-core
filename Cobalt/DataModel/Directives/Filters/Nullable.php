<?php

namespace Cobalt\DataModel\Directives\Filters;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractBoolDirective;
use Override;

#[Attribute()]
class Nullable extends AbstractBoolDirective {
    protected string $label;
    protected ?string $displayValue;
    function __construct(bool|string $nullable = true, string $label = "-- Make a Selection --", ?string $displayValue = null) {
        $this->setLabel($label);
        $this->setDisplayValue($displayValue);
        return parent::__construct($nullable);
    }

    function setLabel(string $label) {
        $this->label = $label;
    }

    function getLabel():string {
        return $this->label;
    }

    function setDisplayValue(?string $onDisplay) {
        $this->displayValue = $onDisplay;
    }

    function getDisplayValue():?string {
        return $this->displayValue;
    }
}