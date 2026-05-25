<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Attributes\Prototype;
use DateTimeInterface;
use Exception;

trait MixedTypeToField {
    #[Prototype]
    protected function pairedTag(string $tagName, array $misc = []) {
        if($this->hasDirective("field")) return $this->getDirective("field", $misc['class'] ?? "", $misc, $tagName);
        $tagName = $this->directiveOrNull('input_tag') ?? $tagName;

    }

    #[Prototype]
    protected function unpairedTag(string $tagName, array $misc = [], string $trailingEnd = "") {
        if($this->hasDirective("field")) return $this->getDirective("field", $misc['class'] ?? "", $misc, $tagName);
        $tagName = $this->directiveOrNull('input_tag') ?? $tagName;

    }

    private function renderAttributes($misc) {
        $type = $this->directiveOrNull('type') ?? $misc['type'] ?? $this->type;
        $attributes = [
            'all' => [
                'type' => null,
                'required' => null,
                'disabled' => null,
                'readonly' => null,
            ],
            'text' => [
                'minlength' => null,
                'maxlength' => null,
            ],
            'number' => [
                'min' => null,
                'max' => null,
            ],
            'range' => [
                'min' => null, 
                'max' => null,
                'step' => null,
            ],
        ];
        $typed_attributes = (key_exists($type, $attributes)) ? $attributes[$type] : [];

        $misc = array_merge(
            $attributes['all'],
            $typed_attributes,
            $misc,
            ['value' => $this->value],
        );


        // $type     = $type ? "type=\"$type\"" : "";
        $value    = $this->value ? "value=\"" . str_replace(['"', "'", '<', '>'], ['&quot;', '&#039;', '&lt;', "&gt;"], $this->value->getValue()) . "\"" : "";
        $disabled = ($misc['disabled'] ?? $this->directiveOrNull('disabled') == true) ? "disabled=\"disabled\"":"";
        $readonly = ($misc['readonly'] ?? $this->directiveOrNull('immutable') == true) ? "readonly=\"readonly\"":"";
        
        $attrs = "";
        

        return "id=\"$this->name\" $type name=\"$this->name\" $value $disabled $readonly";
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input";
        if($tag === null) $tag = "input";
        $prerequisites = "";
        if($this->hasDirective("prerequisites")) $prerequisites = $this->getDirective("prerequisites", $this);
        return $this->input($class, $misc, $tag) . $prerequisites;
    }

    /**
     * The field method returns an editable field
     */
    protected function input($classes = "", $misc = [], $tag = "input"):string {
        $closingTag = "";
        if($tag !== "input") $closingTag = "</$tag>";
        
        if($this->hasDirective("private") && $this->getDirective("private")) return "";
        if($this->hasDirective("immutable") && $this->getDirective("immutable")) $misc['readonly'] = "readonly";
        
        $value = $misc['value'] ?? $this->getValue();
        $pattern = ($this->hasDirective("pattern")) ? $this->getDirective("pattern", false) : "";
        if($pattern) $pattern = " pattern=\"".htmlentities($pattern)."\"";

        [$misc, $attrs] = $this->defaultFieldData($misc);
        return "<$tag class=\"$classes\" $attrs value=\"" . str_replace(
            ['"',      "'",      '<',    '>'],
            ['&quot;', '&#039;', '&lt;', "&gt;"],
            $value) . "\"$pattern>$closingTag";
    }

    protected function inputColor($classes = "", $misc = [], $tag = "input"): string {
        $misc = array_merge($misc, ['type' => 'color']);
        return $this->input($classes, $misc, $tag);
    }

    protected function inputDate($classes = "", $misc = []) {
        $misc = array_merge([
            'from' => $this->schema['from'] ?? 'datetime-local',
            'to'   => $this->schema['to'] ?? 'datetime-local',
        ], $misc);
        [$misc, $attrs] = $this->defaultFieldData($misc);
        
        $fmt = DATETIME_LOCAL_FORMAT;
        switch($misc['to']) {
            case "datetime-local":
            case "w3c-simple":
                $fmt = DATETIME_LOCAL_FORMAT;
                break;
            case "w3c":
                $fmt = DateTimeInterface::W3C;
                break;
            case "seconds":
            case "php":
            case "time":
            case "unix":
            case "milliseconds":
                $fmt = "U";
                break;
            case "ISO 8601":
            case "c":
            case "C":
            default:
                $fmt = "c";
                break;
        }

        $value = $this->getValue();
        $formatted = "";
        if($value) $formatted = $this->format($fmt);

        if($misc['to'] === "milliseconds") $formatted * 1000;

        // $pattern = ($this->hasDirective("pattern")) ? $this->getDirective("pattern", false) : "";
        // if($pattern) $pattern = " pattern=\"".htmlentities($pattern)."\"";
        $prerequisites = "";
        if($this->hasDirective("prerequisites")) $prerequisites = $this->getDirective("prerequisites",$this);
        $nullable = "";
        switch($this->directiveOrNull("nullable")) {
            case null:
            case true:
                $nullable = "<button onclick=\"this.previousSibling.value = '';this.previousSibling.dispatchEvent(new Event('change'));\"><i name='backspace'></i></button>";
                break;
        }

        return "<input type=\"datetime-local\" class=\"$classes\" $attrs value=\"$formatted\">$nullable $prerequisites ";
    }

    protected function select($classes = "", $misc = [], $tag = "select") {
        $selected = "";
        $options = "";
        $datalist = "";
        $datalist_attr = "";
        if($tag === "select") {
            $selected = "<button><selectedcontent></selectedcontent></button>\n";
            $options = $this->options($misc['value'] ?? null);
        } else if ($tag === "input-array") {
            $options = $this->options($misc['value'] ?? null);
        } else {
            $name = $this->datalist_name();
            $datalist = $this->datalist(name: $name);
            $misc['datalist'] = $name;
        }
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $prerequisites = "";
        if($this->hasDirective("prerequisites")) $prerequisites = $this->getDirective("prerequisites",$this);
        return "<$tag class=\"$classes\" $attrs>$selected".$options."</$tag>$datalist"."$prerequisites";
    }

    // abstract public function options($selected = null): string;
    #[Prototype]
    protected function inputAutocomplete($classes = "", $misc = []) {
        $misc['allow-custom'] = $misc['allow_custom'] ?? ($this->directiveOrNull('allow_custom')) ? "allow-custom" : "";
        return $this->select($classes, $misc, "input-autocomplete");
    }

    protected function inputBinary($classes = "", $misc = []) {
        // [$misc, $attrs] = $this->defaultFieldData($misc);
        // $options = $this->binaryOptions();
        // // return $this->select($classes, $misc, "input-binary");
        // return "<input-binary class=\"$classes\" $attrs>$options</input-binary>";
        $misc['value'] = $this->value;
        return $this->select($classes, $misc, "input-binary");
    }

    protected function inputArray($classes = "", $misc = [], $tag = null) {
        if(is_null($tag)) $tag = "input-array";
        return $this->select($classes, $misc, $tag);
    }

    protected function inputObjectArray($classes = "", $misc = []) {
        [$misc, $attrs] = $this->defaultFieldData($misc);

        $template = ($this->hasDirective("view")) ? $this->getDirective("view") : "";
        if($template) $final = view($template, ['doc' => $this, 'field' => $this->value[0]]);
        else {
            $template = ($this->hasDirective("template")) ? $this->getDirective("template") : "";
            $final = view_from_string($template, ['doc' => $this, 'field' => $this->value[0]]);
        }
        if(!$template) throw new Exception("Cannot create a field for ".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}.", must set a 'view' or 'template' directive");
        $prerequisites = "";
        if($this->hasDirective("prerequisites")) $prerequisites = $this->getDirective("prerequisites",$this);

        return "<input-object-array name='".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."' $attrs><template>$final</template><var>".json_encode($this->value)."</var></input-object-array>$prerequisites";
    }

    public function textarea($classes = "", $misc = [], $tag = "textarea") {
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $pattern = ($this->hasDirective("pattern")) ? $this->getDirective("pattern", false) : "";
        if($pattern) $pattern = " pattern=\"".htmlentities($pattern)."\"";
        $prerequisites = "";
        if($this->hasDirective("prerequisites")) $prerequisites = $this->getDirective("prerequisites",$this);
        $fineprint = "";
        if($this->hasDirective("min")) $fineprint .= "<span>Minimum of <strong>".$this->getDirective("min")."</strong> characters.</span>";
        if($this->hasDirective("max")) $fineprint .= "<span>Maximum of <strong>".$this->getDirective("max")."</strong> characters.</span>";
        if($fineprint) $fineprint = "<small>$fineprint</small>";
        return "<$tag class=\"$classes\" $attrs".$pattern.">".$this->getValue()."</$tag>$fineprint"."$prerequisites";
    }

    protected function markdownarea($classes, $misc = []) {
        return $this->textarea($classes, $misc, "markdown-area");
    }

    protected function inputSwitch($classes, $misc = []) {
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $value = json_encode($this->getValue());
        $prerequisites = "";
        if($this->hasDirective("prerequisites")) $prerequisites = $this->getDirective("prerequisites",$this);

        $id = "id=\"".MODEL_MIXED_TYPE_ID_PREFIX."$this->name\"";
        return "<input-switch $id class=\"$classes\" $attrs checked=\"$value\"></input-switch>$prerequisites";
    }

    protected function inputBlock(string $class = "", array $misc = [], string $tag = "block-editor"):string {
        if($this->directiveOrNull("private")) return "";
        if($this->getDirective("immutable")) $misc['readonly'] = 'readonly';
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $prerequisites = "";
        if($this->hasDirective("prerequisites")) $prerequisites = $this->getDirective("prerequisites",$this);

        $id = "id=\"".MODEL_MIXED_TYPE_ID_PREFIX."$this->name\"";
        $html = "<$tag $id class=\"$class\" $attrs>";
        $html .= "<script type=\"application/json\">".json_encode($this->getRaw())."</script>";
        $html .= "</$tag>";
        return $html.$prerequisites;
    }

    function defaultFieldData($misc):array {
        $data = $this->getDefaultFieldAttributes($misc);
        $attributes = [];
        
        foreach($data as $attr => $value) {
            if($attr === "data") {
                $attributes[] = $this->getDataAttributes($value);
                continue;
            }
            $attributes[] = $this->getAttribute($attr, $value);
        }
        return [$data, implode(" ",$attributes)];
    }

    function getDefaultFieldAttributes($misc) {
        return array_merge([
            'id' => MODEL_MIXED_TYPE_ID_PREFIX . $this->name,
            'name' => $this->{MODEL_RESERVERED_FIELD__FIELDNAME} ?? "",
            'type' => $this->type ?? "",
            'min' => $this->directiveOrNull("min") ?? "",
            'max' => $this->directiveOrNull("max") ?? "",
            // 'list' => $this->directiveOrNull("values")->options() ?? "",
            'data' => $misc['data'] ?? [],
            'placeholder' => $misc['placeholder'] ?? $this->directiveOrNull("placeholder") ?? ""
        ], $this->directiveOrNull('input_attrs') ?? [], $misc);
    }

    function getAttribute($attr, $value) {
        $allowedEmptyAttrs = ['open', 'controls', 'disabled'];
        if(($value === "" || $value === null) && !in_array($attr, $allowedEmptyAttrs)) return "";
        if(is_array($value) || is_object($value)) $value = json_encode($value);
        return "$attr=\"".htmlspecialchars($value)."\"";
    }

    function getDataAttributes($data) {
        $d = "";
        foreach ($data as $k => $v) {
            $d .= "data-" . htmlspecialchars($k) . "=\"" . htmlspecialchars($v) . "\"";
        }
        return $d;
    }
}