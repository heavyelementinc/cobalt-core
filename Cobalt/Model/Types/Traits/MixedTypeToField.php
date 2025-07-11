<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Attributes\Prototype;
use DateTimeInterface;
use Exception;

trait MixedTypeToField {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input";
        if($tag === null) $tag = "input";
        return $this->input($class, $misc, $tag);
    }

    /**
     * The field method returns an editable field
     */
    protected function input($classes = "", $misc = [], $tag = "input"):string {
        $closingTag = "";
        if($tag !== "input") $closingTag = "</$tag>";
        
        if($this->hasDirective("private") && $this->getDirective("private")) return "";
        if($this->hasDirective("immutable") && $this->getDirective("immutable")) $misc['readonly'] = "readonly";
        
        $value = $this->getValue();
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

        return "<input type=\"datetime-local\" class=\"$classes\" $attrs value=\"$formatted\">";
    }

    protected function select($classes = "", $misc = [], $tag = "select") {
        [$misc, $attrs] = $this->defaultFieldData($misc);
        
        return "<$tag class=\"$classes\" $attrs>".$this->options()."</$tag>";
    }

    protected function inputAutocomplete($classes = "", $misc = []) {
        return $this->select($classes, $misc, "input-autocomplete");
    }

    protected function inputBinary($classes = "", $misc = []) {
        // [$misc, $attrs] = $this->defaultFieldData($misc);
        // $options = $this->binaryOptions();
        // // return $this->select($classes, $misc, "input-binary");
        // return "<input-binary class=\"$classes\" $attrs>$options</input-binary>";
        return $this->select($classes, $misc, "input-binary");
    }

    protected function inputArray($classes = "", $misc = []) {
        return $this->select($classes, $misc, "input-array");
    }

    protected function inputObjectArray($classes = "", $misc = []) {
        $template = ($this->hasDirective("view")) ? $this->getDirective("view") : "";
        if($template) $final = view($template, ['doc' => $this, 'field' => $this->value[0]]);
        else {
            $template = ($this->hasDirective("template")) ? $this->getDirective("template") : "";
            $final = view_from_string($template, ['doc' => $this, 'field' => $this->value[0]]);
        }
        if(!$template) throw new Exception("Cannot create a field for ".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}.", must set a 'view' or 'template' directive");
        return "<input-object-array name='".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."'><template>$final</template><var>".json_encode($this->value)."</var></input-object-array>";
    }

    public function textarea($classes = "", $misc = [], $tag = "textarea") {
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $pattern = ($this->hasDirective("pattern")) ? $this->getDirective("pattern", false) : "";
        if($pattern) $pattern = " pattern=\"".htmlentities($pattern)."\"";
        return "<$tag class=\"$classes\" $attrs".$pattern.">".$this->getValue()."</$tag>";
    }

    protected function markdownarea($classes, $misc = []) {
        return $this->textarea($classes, $misc, "markdown-area");
    }

    protected function inputSwitch($classes, $misc = []) {
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $value = json_encode($this->getValue());

        return "<input-switch class=\"$classes\" $attrs checked=\"$value\"></input-switch>";
    }

    protected function inputBlock(string $class = "", array $misc = [], string $tag = "block-editor"):string {
        if($this->getDirective("private")) return "";
        if($this->getDirective("immutable")) $misc['readonly'] = 'readonly';
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $html = "<$tag class=\"$class\" $attrs>";
        $html .= "<script type=\"application/json\">".json_encode($this->getRaw())."</script>";
        $html .= "</$tag>";
        return $html;
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
            'id' => '',
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