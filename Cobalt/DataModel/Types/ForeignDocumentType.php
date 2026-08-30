<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Directives\ReferenceModel;
use Cobalt\DataModel\Filters\FilterIssue;
use Cobalt\DataModel\Traits\Joinable;
use Cobalt\DataModel\Types\DocumentType;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use Override;
use TypeError;

/**
 * @package Cobalt\DataModel\Types
 * */
class ForeignDocumentType extends Generic {
    use Joinable;
    protected ?IdType $objectId;

    #[Override]
    function __getLookupPipeline(array &$pipeline):void {
        $pipeline[] = [
            '$lookup' => [
                // Get the external model's collection name...
                'from' => $this->getExternalModel()->getCollectionName(),
                // Get this
                'as' => $this->getFieldDotNotation(),
                'pipeline' => [
                    '$match' => [
                        '_id' => $this->getValue()
                    ]
                ]
            ]
        ];
    }

    public function getExternalModel():DocumentType {
        if(!isset($this->directives->external_model)) {
            throw new Exception("Required directive `external_model` is not defined on ObjectIdType: `".($this->name ?? "%field_name%")."`");
        }
        // Populate this element when its value is actually read and not before!
        return $this->value = $this->directives->external_model->getValue();
    }

    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        $toValidate = IdType::toObjectId($toValidate);
        if(is_string($toValidate)) $this->filterResult->addIssue($this, $toValidate);
        if($toValidate instanceof ObjectId == false) $this->filterResult->addIssue($this, "Must be a database ID");
        return $toValidate;
    }
    #[Override]
    public function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS) {
        return $this->objectId ?? null;
    }

    // TODO: Implement this
    #[Override]
    public function toClientJson(?int $mode = null)  {
        return parent::toClientJson($mode);
    }

    

    /**
     * @param ObjectId $mixed 
     * @return static
     */
    #[Override]
    public function setValue($mixed):void {
        if(is_null($mixed)) {
            $this->objectId = null;
            return;
        }
        $validated = IdType::toObjectId($mixed);
        if(is_string($validated)) throw new TypeError($validated);
        $this->objectId = $mixed;
    }

    /**
     * @return ?DocumentType 
     * @throws Exception 
     */
    #[Override]
    public function getValue(): mixed {
        return $this->value;
    }

    #[Override]
    public function getValidComparisonValues(): ?array {
        return [(string)$this->objectId];
    }

}