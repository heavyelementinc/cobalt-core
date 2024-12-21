<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Types\Traits\SharedFilterEnums;
use Stringable;

class EnumType extends MixedType implements Stringable {
    use SharedFilterEnums;
    
}