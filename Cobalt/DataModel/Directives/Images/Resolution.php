<?php

namespace Cobalt\DataModel\Directives\Image;

use Cobalt\DataModel\Directives\Base\AbstractNumberDirective;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Error;
use Override;
use TypeError;

class Resolution extends DirectiveCommon {
    const FLAG_RESIZE = 0;
    const FLAG_FILTER_FAIL_WITH_ISSUE = 1;

    function __construct(
        int $policy,
        public int $maxWidth,
        public int $maxHeight,
        public ?int $minWidth,
        public ?int $minHeight
    ) {
        return parent::__construct($policy);
    }

    #[Override]
    public function getValue(): mixed {
        return $this->value;
    }

    #[Override]
    function setValue(mixed $value): void {
        switch($value) {
            case self::FLAG_FILTER_FAIL_WITH_ISSUE:
                parent::setValue($value);
                break;
            case self::FLAG_RESIZE:
                throw new Error("Unimplemented flag");
                break;
            default:
                throw new TypeError("Unrecognized flag");
        }
    }

    function filter(string $path, int $width, int $height) {

    /** @var array $max_resolution */
        $max_resolution = ['width' => $this->maxWidth, 'height' => $this->maxHeight];
        /** @var array $min_resolution */
        $min_resolution = ['width' => $this->minWidth, 'height' => $this->minHeight];

        $failed = 0;

        $failed_max_width  = 0b1;
        $failed_max_height = 0b01;
        $failed_min_width  = 0b001;
        $failed_min_height = 0b0001;

        if($max_resolution) {
            if($width > $max_resolution['width']) $failed += $failed_max_width;
            if($height > $max_resolution['height']) $failed += $failed_max_height;
        }

        if($min_resolution) {
            if($width < $min_resolution['width']) $failed = $failed_min_width;
            if($height < $min_resolution['height']) $failed = $failed_min_height;
        }

        if($max_resolution && $min_resolution) {
            if($max_resolution['width'] < $min_resolution['width']) throw $this->filterResult->addIssue($this, "Impossible width constraints");
            if($max_resolution['height'] < $min_resolution['height']) throw $this->filterResult->addIssue($this, "Impossible height constraints");
        }

        

        switch($this->getValue()) {
            case self::FLAG_FILTER_FAIL_WITH_ISSUE:
                $this->type->filterResult->addIssue($this->type, "Image does not conform to $this->width"."x$this->height constraints");
                break;
            case self::FLAG_RESIZE:
                throw new Error("Unimplemented flag");
                break;
            default:
                throw new TypeError("Unrecognized flag");
        }
    }
}