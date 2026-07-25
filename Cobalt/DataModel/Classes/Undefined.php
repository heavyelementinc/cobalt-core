<?php

namespace Cobalt\DataModel\Classes;

use JsonSerializable;
use Override;
use Stringable;

class Undefined implements JsonSerializable, Stringable {
    #[Override]
    public function jsonSerialize(): mixed {
        return null;
    }
    public function __toString(){
        return "";
    }


}