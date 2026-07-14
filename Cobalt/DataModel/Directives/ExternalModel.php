<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Types\DictionaryType;
use Override;

#[Attribute()]
class ExternalModel extends DirectiveCommon {
    protected DictionaryType $value;
    protected string $name = 'external_model';
    #[Override]
    public function setValue(mixed $value): void {
        $this->value = $value;
    }

    #[Override]
    public function getValue(): mixed {
        return $this->value;
    }

}