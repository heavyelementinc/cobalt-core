<?php

namespace Cobalt\DataModel\Directives\Filters;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractArrayDirective;
use Cobalt\DataModel\Filters\FilterIssue;
use MongoDB\BSON\ObjectId;
use Override;
use TypeError;

/**
 * @phpstan-type ValidArray array{value:mixed,originalKey:string|int,optgroup:?string,id:?string,classList:?string,data:?array}
 */

/**
 * 
 * @package Cobalt\DataModel\Directives
 */
#[Attribute()]
class Valid extends AbstractArrayDirective {
    protected string $name = "valid";
    protected array $normalizedKeyValueArray;
    protected array $normalizedMetadata = [];
    
    /**
     * @param string|array<string,string|ValidArray> $array - The name of the method to call or an array of valid options
     * @return void 
     */
    function __construct(string|array $array, protected bool $weak = false){
        if(is_string($array)) $this->isMethod = true;
        parent::__construct($array);
    }

    function isWeak():bool {
        return $this->weak;
    }

    function filter(mixed $toValidate, bool &$isEnumeratedValue):mixed {
        if(key_exists($toValidate, $this->getValue())) {
            // If we have an enumerated value, no need to do any further checks.
            $isEnumeratedValue = true;
            return $toValidate;
        }
        // If we're here, we know we do not have an enumerated value.
        if(!$this->isWeak()) $this->type->filterResult->addIssue($this->type, "Not an enumerated value.");
        // Okay, we have a weak validation set, handle that.
        $isEnumeratedValue = false;
        return $toValidate;
    }

    function display() {
        $normalized = $this->normalized();
        $val = $this->getInstance()->getValue();
        return (key_exists($val, $normalized)) ? $normalized[$val] : $val;
    }

    #[Override]
    function setValue(mixed $value): void {
        unset($this->normalizedKeyValueArray);
        parent::setValue($value);
    }

    function normalized():array {
        if(isset($this->normalizedKeyValueArray)) return $this->normalizedKeyValueArray;
        $values = $this->getValue();
        $this->normalizedKeyValueArray = [];
        $this->normalizedMetadata = [];
        $this->computeFinalizedListOfOptions($values);
        static::normalize_valid_array($values, $this->normalizedKeyValueArray, $this->normalizedMetadata);
        return $this->normalizedKeyValueArray;
    }

    static function normalize_valid_array(array $validKeyValuePairs, array &$normalizedKeyValueArray, array &$normalizedMetadata) {
        $convertKeys = !is_associative_array($validKeyValuePairs);
        $labelKey = "value";
        foreach($validKeyValuePairs as $key => $val) {
            $value = $val;
            if(is_array($val)) {
                if(!key_exists($labelKey, $val)) throw new TypeError("Enumation key `$key` is an array and is missing required key '$labelKey'");
                // $value = $val[$labelKey];
            } else {
                $value = [$labelKey => $val];
            }
            $originalKey = $key;
            if($convertKeys) $key = $value[$labelKey];
            $normalizedKeyValueArray[$key] = $value[$labelKey];
            $normalizedMetadata[$key] = [
                ...$value,
                $labelKey => $value[$labelKey],
                'originalKey' => $originalKey
            ];
        }
    }

    function options(mixed $preselected_value, array $arbitrary_additional_values = []) {
        $arr = $this->normalized();
        /** @var array<string,ValidArray> $meta */
        $meta = $this->normalizedMetadata;

        static::normalize_valid_array($arbitrary_additional_values, $arr, $meta);
        
        // Override label names
        $directives  = $this->getInstance()->directives;
        if($directives->hasDirective("nullable")
            && $directives->nullable->getValue()) {
            if($directives->nullable->getDisplayValue() == $arr['']) {
                $arr[''] = $directives->nullable->getLabel();
            }
        }
        
        $validOptions = $this->type->getValidComparisonValues();
        if($preselected_value) array_push($validOptions, $preselected_value);
        $options = ['General' => ''];
        foreach($arr as $key => $val) {
            $optGroup = $meta[$key]['optGroup'] ?? $meta[$key]['optgroup'] ?? 'General';
            $options[$optGroup] .= $this->option($key, $val, $validOptions, $meta[$key]);
        }
        if(count($options) === 1) return $options['General'];
        $opts = "";
        foreach($options as $groupName => $value) {
            if(!$value) continue;
            $opts .= "<optgroup label=\"$groupName\">\n$value</optgroup>\n";
        }
        return $opts;
    }

    private function computeFinalizedListOfOptions(array &$arr): void {
        $directives = $this->getInstance()->directives;
        if($directives->hasDirective('default')) {
            $default = $directives->default->getValue();
            if(!key_exists($default, $arr)) $arr[$default] = $default;
        }
        
        if($directives->hasDirective('nullable')) {
            if($directives->nullable->getValue() && !key_exists('', $arr)) {
                $arr = ['' => $directives->nullable->getDisplayValue(), ...$arr];
            }
        }
    }

    /**
     * Cobalt option are used in many custom components. As such, we support
     * multiple <option> tags having the `selected` attribute.
     * @param int|string $key 
     * @param mixed $val 
     * @param mixed $preselected_value 
     * @return string 
     */
    protected function option(string|int $key, mixed $val, array $comparisons, mixed $meta):string {
        $attr_selected = "";
        if(in_array($key, $comparisons)) {
            $attr_selected = "selected='selected'";
        }
        
        $attrs = "";
        $this->attributes($meta, $attrs);
        return <<<HTML
        <option $attrs $attr_selected value="$key">$val</option>\n
        HTML;
    }

    private function attributes(?array $meta, string &$attrs) {
        if(key_exists('id', $meta)) $attrs .= "id=\"".htmlspecialchars($meta['id'])."\"";
        if(key_exists('data', $meta)) $this->handleDataAttrs($meta['data'], $attrs);
        if(key_exists('classList', $meta)) $attrs .= " class=\"".htmlspecialchars(is_array($meta['classList']) ? join(" ", $meta['classList']) : $meta['classList'] )."\"";
    }

    private function handleDataAttrs(array|string $data, string &$attrs) {
        if(is_array($data)) {
            foreach($data as $d => $v) {
                $attrs .= " data-$d=\"".htmlspecialchars($v)."\"";
            }
            return;
        }
    }

}