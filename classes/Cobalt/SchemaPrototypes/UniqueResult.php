<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\SchemaPrototypes\Traits\UniqueValidation;
use Drivers\Database;
use Validation\Exceptions\ValidationIssue;

class UniqueResult extends StringResult {
    use UniqueValidation;
    private Database $manager;
    private ?string $fieldName;
    private bool $ignoreSelf;

    function __construct(Database $manager, bool $ignoreSelf = true, string $fieldName = null) {
        $this->manager = $manager;
        $this->fieldName = $fieldName;
        $this->ignoreSelf = $ignoreSelf;
    }

    function setName(string $name) {
        $this->name = $name;
        if($this->fieldName === null) $this->fieldName = $name;
    }

    function filter($value) {
        if(!$this->isUnique($value)) throw new ValidationIssue("This value must be unique.");
        parent::filter($value);
        return $value;
    }
}