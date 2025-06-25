<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Types\Traits\SharedFilterEnums;
use Cobalt\Model\Attributes\Prototype;

use Stringable;

class EnumType extends MixedType implements Stringable {
    use SharedFilterEnums;
    protected string $type = "string";

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "select";
        if($tag === null) $tag = "select";
        return $this->select($class, $misc, $tag);
    }

    #[Prototype]
    protected function get_filter_field($param_value, $param_name, $cast_type) {
        // $v = $this->typecast($current_value, $operation);
        $value = "";
        if($param_value) {
            $value = $param_value[array_search($this->name,$param_value)];
        }
        return view("/Cobalt/Model/templates/filterable/filterable-item.php", [
            'schema' => $this,
            'value' => ($param_name == $this->{MODEL_RESERVERED_FIELD__FIELDNAME}) ? $param_value : "",
            'name' => $this->name,
            'options' => $this->options($value),
            'QUERY_PARAM_FILTER_NAME' => QUERY_PARAM_FILTER_NAME,
            'QUERY_PARAM_FILTER_VALUE' => QUERY_PARAM_FILTER_VALUE
        ]);
    }

    /**
     * Each child of SchemaResult should return an appropriately typecast
     * version of the $value parameter
     * @param mixed $value 
     * @return mixed 
     */
    public function typecast($value, $type = QUERY_TYPE_CAST_LOOKUP) {
        $type = $this->directiveOrNull("typecast") ?? "string";
        return compare_and_juggle($type, $value);
    }
}