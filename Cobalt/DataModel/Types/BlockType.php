<?php

namespace Cobalt\DataModel\Types;

use Override;

class BlockType extends Generic {
    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS)    {
        return $this->value;
    }

    #[Override]
    public function setValue($mixed): void {
        $this->value = $mixed;
    }

    #[Override]
    public function filter(mixed $unserialized, mixed $raw): mixed {
        return $unserialized;
    }
}