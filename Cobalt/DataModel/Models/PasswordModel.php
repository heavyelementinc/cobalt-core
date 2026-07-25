<?php

namespace Cobalt\DataModel\Models;

use Cobalt\DataModel\Directives\DefaultValue;
use Cobalt\DataModel\Types\BooleanType;
use Cobalt\DataModel\Types\DateType;
use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\PasswordHashType;
use Cobalt\DataModel\Types\StringType;
use DateTime;
use Override;

class PasswordModel extends DictionaryType {
    readonly PasswordHashType $hash;
    readonly DateType $lastUpdated;
    
    #[DefaultValue(false)]
    readonly BooleanType $passwordResetRequired;
    // readonly UserId

    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->value;
    }
}