<?php
namespace Cobalt\Auth\Users\Models;

use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\StringType;

class Token extends DictionaryType {
    readonly StringType $label;
    readonly StringType $token;
}