<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Filters\FilterIssue;
use Cobalt\DataModel\Types\ModelType;
use Exception;
use MongoDB\BSON\ObjectId;
use Override;

class ForeignIdType extends Generic {
    protected ObjectId $objectId;

    #[Override]
    public function filter(mixed $toValidate): mixed {
        if(is_string($toValidate)) {
            if(strlen($toValidate) !== 24) $this->filterResult->addIssue($this, "Incorrect length");
            if(preg_match("/^[0-9][a-fA-F\d]{24}$/",$toValidate)) $this->filterResult->addIssue($this, "Contains illegal characters");
            try {
                $toValidate = new ObjectId($toValidate);
            } catch (Exception $e) {
                $this->filterResult->addIssue($this, "An unknown error occurred");
            }
        }
        if($toValidate instanceof ObjectId == false) $this->filterResult->addIssue($this, "Must be a database ID");
        return $toValidate;
    }
    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->objectId ?? null;
    }

    /**
     * @param ObjectId $mixed 
     * @return static
     */
    #[Override]
    public function setValue($mixed):void {
        $this->objectId = $mixed;
    }

    /**
     * @return ?ModelType 
     * @throws Exception 
     */
    #[Override]
    public function getValue(): mixed {
        if(!isset($this->value)) {
            if(!isset($this->directives->external_model)) {
                throw new Exception("Required directive `external_model` is not defined on ObjectIdType: `".($this->name ?? "%field_name%")."`");
            }
            // Populate this element when its value is actually read and not before!
            $this->value = $this->directives->external_model->getValue()->findOne(['_id' => $this->objectId]);
        }
        return $this->value;
    }

    #[Override]
    public function getValidComparisonValues(): ?array {
        return [(string)$this->objectId];
    }

}