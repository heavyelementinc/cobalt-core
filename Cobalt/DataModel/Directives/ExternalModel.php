<?php

namespace Cobalt\DataModel\Directives;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractMixedDirective;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Override;

#[Attribute()]
class ExternalModel extends AbstractMixedDirective {
    protected string $name = "external_model";

    function __construct(DocumentType|string $model, private bool $isMethod = false) {
        return parent::__construct($model, $isMethod);
    }
}