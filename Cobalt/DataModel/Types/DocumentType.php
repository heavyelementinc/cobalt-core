<?php

namespace Cobalt\DataModel\Types;

use Cobalt\Database\Traits\StaticAccessible;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Directives\PrivateValue;
use Cobalt\DataModel\Filters\FilterResult;
use Cobalt\DataModel\Types\StringType;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\DictionaryType;
use Exception;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use stdClass;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * DataModel is the final boss of Cobalt's modeling system.
 * 
 * 
 * 
 * @package Cobalt\DataModel
 */

abstract class DocumentType extends DictionaryType implements Persistable {
    use StaticAccessible;

    // #[PrivateValue()]
    // readonly ArrayType $__job_queue;

    public readonly ObjectId $_id;

    /**
     * This function returns the default string. This is how the field is
     * represented in an index with no index directive or in a delete API call.
     * @return StringType 
     */
    abstract public function getDefaultField(): StringType;

    public function getFieldsetLegend():string {
        $label = $this->directives->fieldset?->value ?? $this->directives->label?->value;
        if(!$label) $label = from_snake_case($this->name);
        return $label ?? "";
    }

    #[Override]
    public function bsonSerialize(): array|stdClass|Document {
        return $this->serialize();
    }

    #[Override]
    public function bsonUnserialize(array $data): void {
        if($this->__isInitialized == false) $this->__construct();
        if(key_exists('_id', $data)) $this->_id = $data['_id'];
        unset($data["_id"], $data['__pclass'], $data['__pClass']);
        $this->setValue($data);
    }

     /**
     * This is canonical entrypoint for validating *all* database updates from 
     * the client in the current model!
     * 
     * This function automatically handles queuing any jobs that may have been
     * scheduled by the filter process (assuming the filter passes without issue).
     * 
     * @param mixed $toValidate 
     * @return FilterResult
     */
    function filterDocument(mixed $toValidate, bool $allowOverloadedFilterFields = false):FilterResult {
        $this->filterResult->setModel($this);
        $this->__allowOverloadedFilterFields = $allowOverloadedFilterFields;
        $this->__filter($toValidate);
        // If our filter comes back successful AND our job has batch items, queue the job
        if(!$this->filterResult->hasIssues() && $this->filterResult->job->hasItems()) {
            // We only want to queue the job here in filterDocument() because this
            // method is the canonincal entrypoint for filtering a document.
            $this->filterResult->job->queue();
        }
        return $this->filterResult;
    }
}