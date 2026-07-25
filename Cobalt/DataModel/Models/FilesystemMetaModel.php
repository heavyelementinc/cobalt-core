<?php

namespace Cobalt\DataModel\Models;

use Cobalt\DataModel\Types\ColorType;
use Cobalt\DataModel\Types\NumberType;
use Cobalt\Model\Types\DictionaryType;
use Cobalt\Model\Types\StringType;

class FilesystemMetaModel extends DictionaryType {
    readonly StringType $mimetype;
    readonly StringType $md5;

}