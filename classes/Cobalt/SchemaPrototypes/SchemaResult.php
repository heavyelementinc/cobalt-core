<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\Schema;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use stdClass;

class SchemaResult implements \Stringable {
    protected $value;
    protected $originalValue;
    protected $type = "mixed";
    protected string $name;
    protected $schema;
    protected Schema $__dataset;
    protected bool $asHTML = false;

    function setName(string $name) {
        $this->name = $name;
    }

    function setValue(mixed $value):void {
        $this->value = $value;
        $this->originalValue = $value;
    }

    function setSchema(?array $schema):void {
        $this->schema = $schema ?? [];
    }

    function datasetReference(Schema $schema):void {
        $this->__dataset = $schema;
    }

    function __toString():string {
        return $this->getValue();
    }

    function __call($name, $arguments) {
        if(key_exists($name, $this->schema) && is_callable($this->schema[$name])) {
            if($arguments) return $this->schema[$name]($this->getValue(), $this, ...$arguments);
            return $this->schema[$name]($this->getValue(), $this);
        }
        if(method_exists($this, $name)) return $this->{$name}($this->getValue($this->getValue(), $this), $this, ...$arguments);
        throw new \BadFunctionCallException("Function `$name` does not exist on `$this->name`");
    }

    /**
     * Text safety means that htmlspecialchars will be applied
     * to this variable if it's a string
     * @param bool $enableAsHTML 
     * @return void 
     */
    public function htmlSafe(bool $enableAsHTML) {
        $this->asHTML = $enableAsHTML;
    }

    public function getValue(): mixed {
        if(key_exists('get', $this->schema) && is_callable($this->schema['get'])) $result = $this->schema['get']();
        else $result = $this->getRaw();
        if($this->asHTML === false && gettype($this->value) === "string") $result = htmlspecialchars($result);
        return $result;
    }

    public function getRaw(): mixed {
        return $this->value;
    }

    public function md() {
        $val = $this->getValue();
        switch($this->type) {
            case "array":
                $val = $this->join(", ");
            case "string":
            case "number":
                return from_markdown($val, $this->asHTML);
        }
    }

    public function raw() {
        return $this->getRaw();
    }

    public function valid():array {
        // if ($field === "pronoun_set") return $this->valid_pronouns();
        if (isset($this->schema['valid'])) {
            return $this->schema['valid'];
        }
        return [];
    }

    public function list($delimiter = ", "):string {
        return implode($delimiter, $this->valid());
    }

    public function options():string {
        $valid = $this->valid();
        $val = $this->getValue();
        if($val instanceof \MongoDB\Model\BSONArray) $gotten_value = $val->getArrayCopy();

        $type = gettype($gotten_value);

        switch($type) {
            // case $val instanceof \MongoDB\Model\BSONArray:
            //     $val = $val->getArrayCopy();
            case "array":
                $v = [];
                foreach($val as $o) {
                    $v[(string)$o] = $o;
                }
                $valid = array_merge($v ?? [], $valid ?? []);
                $type = gettype($val);
        }

        $options = "";
        foreach ($valid as $k => $v) {
            $value = $v;
            $data = "";
            if (gettype($v) === "array") {
                $v = $v['value'];
                unset($value['value']);
                foreach ($value as $attr => $val) {
                    $data .= " data-$attr=\"$val\"";
                }
            }
            $selected = "";
            switch ($type) {
                case "string":
                    $selected = ($val == $k) ? "selected='selected'" : "";
                    break;
                case "object":
                    if ($val instanceof \MongoDB\BSON\ObjectId && (string)$val === $k) {
                        $selected = "selected='selected'";
                    }
                    break;
                case "array":
                    $selected = (in_array($k, $val)) ? "selected='selected'" : "";
                    break;
            }
            $options .= "<option value='$k'$data $selected>$v</option>";
        }
        return $options;
    }

    public function json($pretty = false):string {
        return json_encode($this->value, ($pretty) ? 0 : JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function json_pretty():string {
        return $this->json(true);
    }

    public function length():int|float|null {
        switch($this->type) {
            case "string":
                return strlen($this->getValue());
            case "number":
                return strlen((string)$this->getValue());
            default:
                $val = $this->getValue();
                if(is_countable($val)) return count($val);
                return null;
        }
    }

    public function display():string {
        $valid = $this->valid();
        $type = $this->type;
        $val = $this->getValue();
        switch($type) {
            case "upload":
                return $this->embed();
            case "object":
                if(!is_iterable($val)) {
                    return json_encode($val);
                }
            case "array":
                $items = [];
                $valid = $this->valid();
                foreach($this->getValue() as $v) {
                    if(key_exists($v, $valid)) $items[] = $valid[$v];
                    else $items[] = $v;
                }
                return implode(", ", $items);
            default:
                if(in_array($val, $valid)) return $valid[$val];
                if(in_array($this->value, $valid)) return $valid[$this->value];
                return (string)$val ?? "";
        }
    }
}