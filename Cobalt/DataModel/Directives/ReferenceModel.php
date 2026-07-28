<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Types\DataModel;
use Override;

#[Attribute()]
class ReferenceModel extends DirectiveCommon {
    protected DataModel $value;
    protected string $name = 'reference_model';
    #[Override]
    public function setValue(mixed $value): void {
        $this->value = $value;
    }

    /**
     * Returns the document
     * @return DataModel
     */
    #[Override]
    public function getValue(): mixed {
        return $this->value;
    }

}
