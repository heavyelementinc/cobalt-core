<?php

namespace Cobalt\DataModel\Directives\Types\Composite;

use Cobalt\DataModel\Types\StringType;
use ParsedownExtra;

class MarkdownType extends StringType {
    function strip(bool $safeMode = true) {
        return strip_tags($this->md($safeMode));
    }

    function md(bool $safeMode = true) {
        $pd = new ParsedownExtra();
        $pd->setSafeMode($safeMode);
        return $pd->text($this->value);
    }
}