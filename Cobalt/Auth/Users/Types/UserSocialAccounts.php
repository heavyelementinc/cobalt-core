<?php

namespace Cobalt\Auth\UserAccounts\Types;

use Cobalt\DefinedModel\GenericModel;
use Cobalt\DefinedModel\Traits\ModelInitialize;
use Cobalt\Model\Types\StringType;

class UserSocialAccounts extends GenericModel {
    use ModelInitialize;
    
    readonly StringType $fediverse;
    readonly StringType $facebook;
    readonly StringType $twitter;
    readonly StringType $instagram;
    readonly StringType $youtube;
}