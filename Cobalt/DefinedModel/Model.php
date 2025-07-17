<?php

namespace Cobalt\DefinedModel;

use Cobalt\DefinedModel\Traits\ModelUpdate;
use Cobalt\Model\Traits\Accessible;
use Cobalt\Model\Types\MixedType;

abstract class Model extends GenericModel {
    use Accessible, ModelUpdate;

}