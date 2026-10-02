<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractMixedDirective;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Override;
use TypeError;

#[Attribute()]
class ExternalModel extends AbstractMixedDirective {
    protected string $name = "external_model";

    function __construct(DocumentType|string $model, bool $isMethod = false) {
        return parent::__construct($model, $isMethod);
    }

    /**
     * @return ?DocumentType
     */
    #[Override]
    public function getValue(): ?DocumentType {
        if($this->isMethod || is_string($this->value)) return $this->callModelMethod($this->value, [$this->type->raw, $this, $this->_reference]);
        return $this->value;
    }
}