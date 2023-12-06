<?php

namespace Cobalt\SchemaPrototypes;

use Cobalt\PersistanceMap;
use Exception;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use stdClass;

/** ## `SchemaResult` schema directives
 *  * `default` => [null], the default value of the an element
 *  * `valid`   => [array], an enumerated list of valid values
 *  * `md_preserve_tags` => `bool` determines if the markdown parser should preserve HTML tags
 * @package Cobalt\SchemaPrototypes 
 * */
class SchemaResult implements \Stringable {
    protected $value;
    protected $originalValue;
    protected $type = "mixed";
    protected string $name;
    protected $schema;
    protected PersistanceMap $__reference;
    protected bool $asHTML = false;

    public function getValue(): mixed {
        if(key_exists('get', $this->schema) && is_callable($this->schema['get'])) $result = $this->schema['get']($this->value, $this);
        else $result = $this->getRaw();
        if($this->asHTML === false && gettype($this->value) === "string") $result = htmlspecialchars($result);
        return $result;
    }

    public function getRaw(): mixed {
        return $this->value;
    }

    public function md() {
        $val = $this->getValue();
        $asHtml = $this->asHTML;
        if($this->schema['md_preserve_tags'] === true) $asHtml = true;
        switch($this->type) {
            case "array":
                $val = $this->join(", ");
            case "string":
            case "number":
                return from_markdown($val, $asHtml);
        }
    }

    public function raw() {
        return $this->getRaw();
    }

    public function getValid():array {
        // if ($field === "pronoun_set") return $this->valid_pronouns();
        if (isset($this->schema['valid'])) {
            if(is_callable($this->schema['valid'])) return $this->valid($this->getValid(), $this);
            return $this->schema['valid'];
        }
        return [];
    }

    public function list($delimiter = ", "):string {
        return implode($delimiter, $this->getValid());
    }

    public function options():string {
        $valid = $this->getValid();
        $val = $this->getValue() ?? $this->value;
        // if($val instanceof \MongoDB\Model\BSONArray) $gotten_value = $val->getArrayCopy();

        $type = gettype($val);

        switch($type) {
            // case $val instanceof \MongoDB\Model\BSONArray:
            //     $val = $val->getArrayCopy();
            case "array":
                $validValue = [];
                foreach($val as $o) {
                    $validValue[(string)$o] = $o;
                }
                $valid = array_merge($validValue ?? [], $valid ?? []);
                $type = gettype($val);
        }

        $options = "";
        foreach ($valid as $validKey => $validValue) {
            $value = $validValue;
            $data = "";
            if (gettype($validValue) === "array") {
                $validValue = $validValue['value'];
                unset($value['value']);
                foreach ($value as $attr => $val) {
                    $data .= " data-$attr=\"$val\"";
                }
            }
            $selected = "";
            switch ($type) {
                case "string":
                    $selected = ($val == $validKey) ? "selected='selected'" : "";
                    break;
                case "object":
                    if ($val instanceof \MongoDB\BSON\ObjectId && (string)$val === $validKey) {
                        $selected = "selected='selected'";
                    }
                    break;
                case "array":
                    $selected = (in_array($validKey, $val)) ? "selected='selected'" : "";
                    break;
            }
            $options .= "<option value='$validKey'$data $selected>$validValue</option>";
        }
        return $options;
    }

    public function json($pretty = false):string {
        return json_encode($this->value, ($pretty) ? 0 : JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function json_pretty():string {
        return $this->json(true);
    }

    public function field($classes = "", $misc = []) {
        $misc = $this->defaultFieldData($misc);
        $name = $this->name;
        if(key_exists('name', $misc)) $name = $misc['name'];
        $value = $this->getValue();
        return "<input type=\"$this->type\" class=\"$classes\" name=\"$name\"$misc[data]\" value=\"". htmlspecialchars($value) ."\">";
    }

    public function defaultFieldData($misc) {
        $data = array_merge([
            'id' => '',
            
            'data' => $misc['data'] ?? [],
        ],$misc);
        $d = "";
        foreach($data['data'] as $k => $v) {
            $d .= "data-".htmlspecialchars($k)."=\"".htmlspecialchars($v)."\"";
        }
        $data['data'] = $d;
        return $data;
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
        $valid = $this->getValid();
        $type = $this->type;
        $val = $this->getValue();
        switch($type) {
            case "upload":
                return $this->embed();
            case "object":
                if(is_a($val, "\\Cobalt\\SchemaPrototypes\\SchemaResult")) {
                    return $val->getValue();
                }
                if(!is_iterable($val)) {
                    return json_encode($val);
                }
            case "array":
                $items = [];
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


    
    /**
     * Sets the name of the schema item. By convention, this should be the 
     * [name='<some_name>'] attribute. Also, by convention, this should use
     * underscores and not dashes
     * @param string $name 
     * @return void 
     */
    function setName(string $name) {
        $this->name = $name;
    }

    /**
     * This sets the value of this item. This should *not* be confused with
     * the 'set' directive which is used for validation purposes
     * @param mixed $value 
     * @return void 
     */
    function setValue(mixed $value):void {
        $this->originalValue = $value;
        if($value === null) $this->value = $this->schema['default'];
        else $this->value = $value;
    }

    /**
     * Stores the list of schema directives for this item
     * @param null|array $schema 
     * @return void 
     */
    function setSchema(?array $schema):void {
        $this->schema = array_merge($this->defaultSchemaValues(), $schema ?? []);
    }

    /**
     * A function that supplies the default schema directives for a given
     * SchemaResult type. By convention, all valid directives should be
     * defined here unless doing so would cause issues
     * @param array $data 
     * @return array 
     */
    function defaultSchemaValues(array $data = []): array {
        return array_merge([
            'default' => null,
            'nullable' => false,
            'required' => false,
            'md_preserve_tags' => false,
        ], $data);
    }

    /**
     * Sets a reference to the parent PersistanceMap definition
     * @param PersistanceMap $schema 
     * @return void 
     */
    function datasetReference(PersistanceMap &$schema):void {
    $this->__reference = $schema;
    }

    function __toString():string {
        return $this->getValue() ?? "";
    }

    function __call($name, $arguments) {
        $schema = $this->schema;
        if(key_exists($name, $schema) && is_callable($schema[$name])) {
            if($arguments) return $schema[$name]($this->getValue(), $this, ...$arguments);
            return $schema[$name]($this->getValue(), $this);
        }
        if(method_exists($this, $name)) return $this->{$name}($this->getValue($this->getValue(), $this), $this, ...$arguments);
        throw new \BadFunctionCallException("Function `$name` does not exist on `$this->name`");
    }

    function __get($path) {
        return lookup_js_notation($path, $this->value);
    }

    /**
     * TODO: Make this less jank
     * @param mixed $path 
     * @return bool 
     */
    function __isset($path) {
        try {
            $result = lookup_js_notation($path, $this->value, true);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    function __isRequired(): bool {
        return $this->coalece_directive('required');
    }

    function __isNullable(): bool {
        return $this->coalece_directive('nullable');
    }

    protected function coalece_directive($field, $defaultsTo = false):bool{ 
        if(!isset($this->schema[$field])) return $defaultsTo;
        $req = $this->schema[$field];
        if(gettype($req) === "boolean") return $req;
        return $defaultsTo;
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
}