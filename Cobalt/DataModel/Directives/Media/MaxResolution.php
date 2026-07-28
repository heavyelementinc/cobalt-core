<?php

namespace Cobalt\DataModel\Directives\Media;

use Cobalt\DataModel\Classes\Undefined;
use Cobalt\DataModel\Directives\Base\AbstractArrayDirective;
use Override;
use TypeError;

class MaxResolution extends AbstractArrayDirective {
    protected string $name = "max_resolution";

    /**
     * 
     * @param array{width:int,height:int}|string $array 
     * @return void 
     */
    public function __construct(array|string $array) {
        return parent::__construct($array);
    }

    function __get($name) {
        switch($name) {
            case 0:
            case "0":
            case "width":
                return $this->value['width'];
            case 1:
            case "1":
            case "height":
                return $this->value['height'];
            default:
                throw new TypeError("Undefined $name");
        }
    }

    #[Override]
    function setValue(mixed $value): void {
        if(array_is_list($value)) {
            $value = ['width' => $value[0], 'height' => $value[1]];
        }
        parent::setValue($value);
    }
}