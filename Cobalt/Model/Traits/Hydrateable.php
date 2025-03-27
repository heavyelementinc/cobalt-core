<?php
namespace Cobalt\Model\Traits;

use Cobalt\Model\Attributes\DoNotSet;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\ObjectType;
use Cobalt\Model\Types\StringType;
use MongoDB\BSON\Document;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

trait Hydrateable {
    /**
     * This function acts as a means of consistently populating a MixedType
     * @param array &$target This is usually $this->__dataset
     * @param string|int $field_name The name of the field we're calling
     * @param mixed $value The value that field should be set to
     * @param null|GenericModel $model The model to which this field belongs
     * @param mixed $name 
     * @return void 
     */
    protected function hydrate(array &$target, string|int $field_name, $value, ?GenericModel $model = null, $name = null, ?array $directives = [], ?MixedType $instance = null):void {
        // Let's find our instance and get ready to modify it
        if($instance === null) {
            /** @var MixedType $instance */
            if(isset($directives['type'])) $instance = $directives['type'];
            else $instance = $this->implicit_cast($field_name, $value, $target);
        }
        
        // Now that we have our instance, let's configure it
        $instance->setName($name); // We set up our name first since that's critical
        $instance->setModel($model); // Then, we point to the instancing model
        $instance->setDirectives($directives); // Finally, we set the directives
        // If we can, we'll set our value right now.
        if($value instanceof DoNotSet === false) $instance->setValue($value);

        // In case we're not setting the value, let's perform this final pass at initializing the MixedType
        $instance->finalInitialization();
        $target[$field_name] = $instance;
    }

    function normalizeMongoDocuments(&$value, $instance = null) {
        if($value instanceof Document) {
            $instance = new ModelType();
        }
        if($value instanceof BSONArray) {
            $instance = new ArrayType();
            $value = $value->getArrayCopy();
        }
        if($value instanceof BSONDocument) {
            $instance = new ModelType();
            $value = $value->getArrayCopy();
        }
        if($instance === null) {
            $instance = new MixedType();
        }
        return $instance;
    }

    function implicit_cast(string $field, mixed $value): MixedType {
        $type = gettype($value);
        switch($type) {
            case "string":
                $instance = new StringType();
                break;
            case "integer":
            case "int":
            case "float":
            case "double":
                $instance = new NumberType();
                break;
            case "array":
                if(is_associative_array($value)) {
                    $instance = new ModelType();
                } else $instance = new ArrayType();
                break;
            case "object":
                $instance = $this->normalizeMongoDocuments($value);
                break;
            default:
                $instance = new MixedType();
        }

        return $instance;
    }
}