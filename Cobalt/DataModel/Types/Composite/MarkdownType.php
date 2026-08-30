<?php

namespace Cobalt\DataModel\Types\Composite;

use Cobalt\DataModel\Types\StringType;
use Override;
use ParsedownExtra;

class MarkdownType extends StringType {
    function strip(bool $safeMode = true) {
        return strip_tags($this->md($safeMode));
    }

    function md(bool $safeMode = true) {
        $pd = new ParsedownExtra();
        $pd->setSafeMode($safeMode);
        return $pd->text($this->getValue() ?? "");
    }

    #[Override]
    function toClientJson(?int $mode = null) {
        if($mode & self::SERIALIZE_MODE_VALUE_DISPLAY) return $this->md(false);
        // return parent::toClientJson($mode);
        return $this->getValue();
    }
}