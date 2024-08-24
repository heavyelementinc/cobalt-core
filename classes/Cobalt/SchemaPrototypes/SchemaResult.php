<?php

/**
 * The SchemaResult is a base class that adds prototypical behavior to PHP.
 * 
 * SchemaResults work best with PersistanceMaps and schema directives. PersistanceMaps
 * utilize MongoDB driver persistance to store and recall documents as PHP classes.
 * 
 * You can think of PersistanceMaps as a means of storing methods and metadata
 * alongside the values in a database.
 * 
 * All SchemaResults share 
 * 
 * By convention, functions defined as `public` denote prototype functions for
 * this class.
 *  
 * @package Cobalt\SchemaPrototypes
 * @author Gardiner Bryant, Heavy Element
 * @copyright 2023 Heavy Element
 */

namespace Cobalt\SchemaPrototypes;

use Exception;
use TypeError;
use JsonSerializable;
use BadFunctionCallException;
use ReflectionObject;
use ReflectionException;
use MongoDB\Model\BSONArray;
use MongoDB\BSON\Persistable;
use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use Cobalt\Maps\Exceptions\DirectiveException;
use Cobalt\Renderer\Exceptions\TemplateException;
use MongoDB\Model\BSONDocument;

/** ## `SchemaResult` schema directives
 *  * `default` => [null], the default value of the an element
 *  * `valid`   => [array], an enumerated list of valid values
 *  * `md_preserve_tags` => `bool` determines if the markdown parser should preserve HTML tags
 *  * `private` => `bool` prevents a field from being serialized to JSON and prevents typecasting to string (returns ""),
 *  * `pattern` => `string` a regex-like pattern used to filter inputs BEFORE the native filter callback and included as an attribute in the field() callback
 *  * `pattern_flags` => 'string' flags
 *  * `input_tag` => specify any input HTML tag and the field prototype will return that tag (for most SchemaResult children)
 * @package Cobalt\SchemaPrototypes 
 * */
class SchemaResult implements \Stringable, JsonSerializable {
    protected $value;
    protected $originalValue;
    protected $type = "mixed";
    protected string $name;
    protected $schema;
    protected GenericMap $__reference;
    protected bool $asHTML = false;

    public function jsonSerialize(): mixed {
        return $this->originalValue;
    }

    public function __getStorable(): mixed {
        if($this instanceof Persistable) return $this;
        return $this->originalValue;
    }

    /**
     * Get the *processed* value of this method. This will return the
     * raw value passed through any defined `get` directive **AND** 
     * will mutate the value via the htmlspecialchars value if the
     * `asHTML` property is set to `false`.
     * @return mixed
     */
    public function getValue(): mixed {
        $result = $this->value;
        if ($result === null) {
            if($this->schema['default']) $result = $this->getDirective('default');
            // if($this->schema['fallback']) $result = $this->getDirective('fallback');
            // $result = $this->schema['default'];
            // if(is_callable($result)) $result = $result();
        }
        if (key_exists('get', $this->schema ?? []) && is_callable($this->schema['get'])) $result = $this->schema['get']($result, $this);
        else $result = $this->getRaw();
        if ($this->asHTML === false && gettype($this->value) === "string") $result = htmlspecialchars($result);
        return $result;
    }

    /**
     * Set the value of this item. This should *not* be confused with
     * the 'set' directive which is used for validation purposes
     * @param mixed $value 
     * @return void 
     */
    function setValue(mixed $value): void {
        $this->originalValue = $value;
        if ($value === null) $this->value = $this->schema['default'];
        else $this->value = $value;
    }

    /**
     * When $enableAsHTML is `false`, htmlspecialchars will be applied
     * to this variable.
     * @param bool $enableAsHTML 
     * @return void 
     */
    public function htmlSafe(bool $enableAsHTML) {
        $this->asHTML = $enableAsHTML;
    }

    /**
     * GetRaw will return the raw value with no processing whatsoever.
     * If no value is stored, hydration is turned off, or if the value
     * is dynamically derived from the `get` directive, this will be nullish.
     * @return mixed
     */
    public function getRaw(): mixed {
        return $this->value;
    }

    public function readSchema():array {
        return $this->schema;
    }

    public function getLabel() {
        $this->name;
        $name = preg_replace("/[-_]/", " ", $this->name);
        return ucwords($name);
    }

    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/

    #[Prototype]
    protected function raw() {
        return $this->getRaw();
    }

    #[Prototype]
    protected function md() {
        $val = $this->getValue();
        $asHtml = $this->asHTML;
        if ($this->schema['md_preserve_tags'] === true) $asHtml = true;
        switch ($this->type) {
            case "array":
                $val = $this->join(", ");
            case "string":
            case "number":
                return from_markdown($val, $asHtml);
        }
    }

    /**
     * Get the list of valid values for this field. This is defined by the 
     * `valid` array or delta function directive.
     * 
     * `valid` delta directives MUST return an array or iterable.
     * 
     * @return array
     */
    #[Prototype]
    protected function getValid(): array {
        // if ($field === "pronoun_set") return $this->valid_pronouns();
        if (isset($this->schema['valid'])) {
            if (is_callable($this->schema['valid'])) {
                $val = $this->getDirective('valid');
                if (is_array($val)) return $val;
                if ($val instanceof BSONArray) return $val->getArrayCopy();
                if ($val instanceof BSONDocument) return (array)$val;
                if (is_iterable($val)) return iterator_to_array($val);
                throw new Exception("Return value for $this->name's `valid` directive is not an array or iterable!");
            }
            return $this->schema['valid'];
        }
        return [];
    }

    /**
     * The `options` method will return an string of <option> tags based on
     * the return value of the `getValid()` method. The current value of this
     * field will have the `selected="selected"` attribute set.
     * 
     * This is useful for the native <select> element, the <input-array> component,
     * and the <input-autocomplete> component.
     * @return string
     */
    #[Prototype]
    protected function options($selected = null): string {
        $valid = $this->getValid();
        
        if($selected) {
            if($this->getDirective("allow_custom")) $val = $selected;
            else if (key_exists($selected, $valid)) $val = $selected;
            else $val = $this->getValue() ?? $this->value;
        } else $val = $this->getValue() ?? $this->value;

        // if(!is_string($val) && is_numeric($val)) $val = "$val";
        // if($val instanceof \MongoDB\Model\BSONArray) $gotten_value = $val->getArrayCopy();
        
        // If custom is allowed
        $allow_custom = $this->getDirective("strict") === false;
        if(!$allow_custom) $allow_custom = $this->getDirective("allow_custom");

        // If the current value is not a key in the current valid options AND
        // we're allowed to have custom options, add the current val to the options
        if($allow_custom && $val && !key_exists($val, $valid)) $valid += [$val => $val];

        $type = gettype($val);

        switch ($type) {
                // case $val instanceof \MongoDB\Model\BSONArray:
                //     $val = $val->getArrayCopy();
            case "array":
                $validValue = [];
                foreach ($val as $o) {
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
                case "integer":
                case "double":
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


    #[Prototype]
    protected function list($delimiter = ", "): string {
        return implode($delimiter, $this->getValid());
    }

    /**
     * This function returns the value serialized as JSON
     * @param bool $pretty if set to pretty then JSON_PRETTY_PRING and JSON_UNESCAPED_SLASHES will be passed to `json_encode`
     * @return string
     */
    #[Prototype]
     protected function json($pretty = false): string {
        if($this->__isPrivate()) return "";
        return json_encode($this->value, ($pretty) ? 0 : JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    #[Prototype]
    protected function json_pretty(): string {
        return $this->json(true);
    }

    /**
     * Depending on the defined `type` property, the this function will
     * return different results.
     *  * Strings return the character count
     *  * Numbers return the string-ified character count of the number
     *  * Arrays and other countables return the result of `count($var)`
     *  * Any other value types return null
     * @return int|null the length the string or countable
     */
    #[Prototype]
    protected function length(): int|null {
        switch ($this->type) {
            case "string":
                return strlen($this->getValue());
            case "number":
                return strlen((string)$this->getValue());
            default:
                $val = $this->getValue();
                if (is_countable($val)) return count($val);
                return null;
        }
    }

    /**
     * The display function will return 
     * 
     *  * For UploadResult, the embed function will be returned using the default `media` value
     *  * For Enums, Numbers, and Strings, the enumerated public `valid` value will be returned *or* the actual value if the key doesn't exist 
     *  * For an ArrayResult the enumerated values for each array element, joined with a ", "
     */
    #[Prototype]
    protected function display(): string {
        $valid = $this->getValid();
        $type = $this->type;
        $val = $this->getValue();

        // Since 'display' is already a method, we need to manually invoke the 
        // `display` directive if it exists.
        $directive = $this->getDirective("display");
        if(is_callable($directive)) return $directive($val, $this->name, $valid);

        switch ($type) {
            case "upload":
                return $this->embed();
            case "object":
                if (is_a($val, "\\Cobalt\\SchemaPrototypes\\SchemaResult")) {
                    return $val->getValue();
                }
                if (!is_iterable($val)) {
                    return json_encode($val);
                }
            case "array":
                $items = [];
                foreach ($this->getValue() as $v) {
                    if (key_exists($v, $valid)) $items[] = $valid[$v];
                    else $items[] = $v;
                }
                return implode(", ", $items);
            default:
                if (in_array($val, $valid)) return $valid[$val];
                if (in_array($this->value, $valid)) return $valid[$this->value];
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

    final const universalSchemaDirectives = [
        'default' => null,
        'nullable' => false,
        'required' => false,
        'md_preserve_tags' => false,
        'pattern' => '',
        'pattern_flags' => '',
    ];

    /**
     * Stores the list of schema directives for this item
     * @param null|array $schema 
     * @return void 
     */
    function setSchema(null|array $schema): void {
        $this->schema = array_merge(
            self::universalSchemaDirectives,
            $this->defaultSchemaValues(),
            $schema ?? []
        );
    }

    function getSchema(): ?array {
        return $this->schema;
    }

    /**
     * A function that supplies the default schema directives for a given
     * SchemaResult type. By convention, all valid directives should be
     * defined here unless doing so would cause issues
     * @param array $data 
     * @return array 
     */
    function defaultSchemaValues(array $data = []): array {
        return $data;
    }

    /**
     * Sets a reference to the parent PersistanceMap definition
     * @param GenericMap $schema 
     * @return void 
     */
    function datasetReference(GenericMap &$schema): void {
        $this->__reference = $schema;
    }

    function __defaultIndexPresentation(): string {
        return $this->__toString();
    }

    function __toString(): string {
        if($this->__isPrivate()) return "";
        $read = $this->getValue();
        $type = gettype($read);
        return $read ?? "";
    }

    #[Prototype]
    protected function cast($type) {
        switch(strtolower($type)) {
            case "string":
                return (string)$this->getValue();
            case "int":
            case "integer":
                return (int)$this->getValue();
            case "float":
            case "double":
                return (float)$this->getValue();
            case "array":
                return (array)$this->getValue();
            default:
                throw new TypeError("Cannot cast '$this->name' to type `$type`");
        }
    }

    /**
     * How prototypes are called:
     *  - If a method is `public`, then that method gets called and bypasses the __call function
     *  - If a Directive Protoype exists, then it's called and its value is returned
     *  - If a built-in Prototype exists, then it's called and its value is returned
     *  - A BadFunctionCallException is thrown if the above fails to return a value
     * 
     * ## DIRECTIVE PROTOTYPES
     * Defining: you may define any arbitrary prototype method in a field's schema directives
     * which will override any built-in prototypes of the same name.
     * 
     * Directive Prototypes are always passed ($this->getValue(), $this, ...$args)
     * 
     * ## BUILT-IN PROTOTYPES
     * Defining: a prototype must be defined as `protected` and have the #[Prototype] attribute set,
     * otherwise, this callback will throw a BadFunctionCallException.
     * 
     * Methods defined as protected will be overridable by Directive Prototypes, otherwise they will not.
     * 
     * Built-in prototypes are always passed (...$args) since they're already referencing $this
     * 
     * @param string $name - The name of the function being called
     * @param mixed $arguments - The arguments
     * @return mixed - The return value of the method
     * @throws ReflectionException
     * @throws BadFunctionCallException
     */
    function __call($name, $arguments) {
        $schema = $this->schema ?? [];
        $args = $arguments ?? [];
        if (key_exists($name, $schema) && is_callable($schema[$name])) {
            return $schema[$name]($this->getValue(), $this, ...$args);
        }
        if (method_exists($this, $name)) {
            if($this->__isPrototypeAttributeSet($this, $name) === false) throw new \BadFunctionCallException("Method lacks #[Prototype] attribute");
            return $this->{$name}(...$args);
        }
        throw new \BadFunctionCallException("Function `$name` does not exist on `$this->name`");
    }

    /**
     * Uses the ReflectionObject class to check if a given method is defined as a prototype
     * using the #[Prototype] attribute.
     * 
     * @param SchemaResult $class 
     * @param string $methodName 
     * @return ?bool Returns `true` if prototype attribute is found, `false` if not, `null` if the method does not exist
     * @throws ReflectionException 
     */
    function __isPrototypeAttributeSet(SchemaResult $class, string $methodName):?bool {
        $reflection = new ReflectionObject($class);
        $method = $reflection->getMethod($methodName);
        if(!$method) return null;//throw new \BadMethodCallException("Call for `$methodName` is invalid on `$this->name`");
        $attributes = $method->getAttributes();
        $validPrototypes = ["Prototype", "Cobalt\SchemaPrototypes\Traits\Prototype"];
        foreach($attributes as $attr) {
            if(in_array($attr->getName(), $validPrototypes)) return true;
        }
        return false;
    }
    
    /**
     * Directive methods will always be called with the following arguments:
     *  [$this->getValue(), $this, ...[other_args]]
     * @param mixed $directiveName - The name of the directive to fetch
     * @param bool $throwOnFail - If the directive does not exist, return `null` if throwOnFail is `false`, otherwise will throw a `DirectiveException`
     * @return mixed returns the value of the directive callable *or* the directive literal value
     * @throws DirectiveException 
     */
    public function getDirective($directiveName, $throwOnFail = false) {
        if(!key_exists($directiveName, $this->schema ?? [])) {
            if($throwOnFail) throw new DirectiveException("Undefined value");
            return null;
        }
        if(is_callable($this->schema[$directiveName]) && gettype($this->schema[$directiveName]) !== "string") {
            $args = func_get_args();
            return $this->schema[$directiveName]($this->getValue(), $this, ...array_slice($args, 2));
        }
        return $this->schema[$directiveName];
    }

    function __get($path) {
        return lookup($path, $this->value);
    }

    /**
     * TODO: Make this less jank
     * @param mixed $path 
     * @return bool 
     */
    function __isset($path) {
        try {
            $result = lookup_js_notation($path, $this->getValue(), true);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    // function __set($name, $value) {
    //     if($name !== "name") return;
    //     $this->name = $value;
    // }

    function __isRequired(): bool {
        return $this->coalece_directive('required');
    }

    function __isNullable(): bool {
        return $this->coalece_directive('nullable');
    }

    function __isPrivate(): bool {
        return $this->coalece_directive('private');
    }

    /**
     * Coaleces a directive value into either true or false
     * @param string $field 
     * @param bool $defaultsTo 
     * @return bool 
     */
    protected function coalece_directive(string $field, bool $defaultsTo = false): bool {
        if (!isset($this->schema[$field])) return $defaultsTo;
        $req = $this->schema[$field];
        if (gettype($req) === "boolean") return $req;
        return !!$req;
    }

    protected function queriableName($name) {
        return str_replace(".", "__", $name);
    }

    protected function isStrict(): bool {
        $valid_key_exists = key_exists('valid', $this->schema);
        if($valid_key_exists === false) return false;

        $strict_directive = $this->getDirective('strict');
        $allow_custom = $this->getDirective('allow_custom');
        // If `strict_directive` is false
        if(!$strict_directive) {
            // And `allow_custom` is false
            if(!$allow_custom) return false;
            
        }

        // If we're here, that means that strict_directive is true
        // So let's check if allow_custom is `true`.
        if($allow_custom === true) return false;

        return true;
    }

    /**
     * Each child of SchemaResult should return an appropriately typecast
     * version of the $value parameter
     * @param mixed $value 
     * @return mixed 
     */
    public function typecast($value, $type = QUERY_TYPE_CAST_LOOKUP) {
        if($this->type === "mixed") return $value;
        return compare_and_juggle($this->type, $value);
    }

    #[Prototype]
    protected function get_filter_field($current_value, $operation = QUERY_TYPE_CAST_LOOKUP) {
        $v = $this->typecast($current_value, $operation);
        return view("/admin/crudable/filterable-item.html", [
            'schema' => $this,
            'value' => $v,
            'name' => $this->name,
            'options' => $this->options($v),
            'QUERY_PARAM_FILTER_NAME' => QUERY_PARAM_FILTER_NAME,
            'QUERY_PARAM_FILTER_VALUE' => QUERY_PARAM_FILTER_VALUE
        ]);
    }
}
