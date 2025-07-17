<?php

namespace Cobalt\Auth\UserAccounts\Types;

use Cobalt\DefinedModel\GenericModel;
use Cobalt\DefinedModel\Traits\ModelInitialize;
use Cobalt\Model\Types\StringType;

class UserPreferences extends GenericModel {
    use ModelInitialize;
    
    readonly StringType $preferneces;
    
}