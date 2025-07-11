<?php

namespace Cobalt\SchemaPrototypes\Traits;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use Exception;

trait Fieldable {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($tag === null) $tag = $this->getDirective("input_tag") ?? "input";
        return $this->input($class, $misc, $tag);
    }

    // #[Prototype]
    // abstract protected function field(string $class = "", array $misc = [], string $tag = ""):string;

    /**
     * The field method returns an editable field
     */
    protected function input($classes = "", $misc = [], $tag = "input"):string {
        $closingTag = "";
        if($tag !== "input") $closingTag = "</$tag>";
        
        if($this->getDirective("private")) return "";
        if($this->getDirective("immutable")) $misc['readonly'] = 'readonly';
        
        $value = $this->getValue();
        $pattern = $this->getDirective("pattern", false);
        if($pattern) $pattern = " pattern=\"".htmlentities($pattern)."\"";

        [$misc, $attrs] = $this->defaultFieldData($misc);
        return "<$tag class=\"$classes\" $attrs value=\"" . str_replace(
            ['"',      "'",      '<',    '>'],
            ['&quot;', '&#039;', '&lt;', "&gt;"],
            $value) . "\"$pattern>$closingTag";
    }

    protected function inputDate($classes = "", $misc = []) {
        $value = $this->getValue();
        // $format = $value->format("c");
        // return "<input type='datetime-local' name='$this->name' value='$format'>";
        $misc = array_merge([
            'from' => $this->schema['from'] ?? "datetime-local",
            'to'   => $this->schema['to'] ?? "datetime-local",
        ], $misc);
        [$misc, $attrs] = $this->defaultFieldData($misc);
        
        $fmt = "c";
        switch($misc['from']) {
            case "datetime-local":
                $fmt = DATETIME_LOCAL_FORMAT;
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
        if($value) $formatted = $value->format($fmt);

        if($misc['from'] === "milliseconds") $formatted * 1000;

        // $pattern = $this->getDirective("pattern", false);
        // if($pattern) $pattern = " pattern=\"".htmlentities($pattern)."\"";
        
        return "<input type=\"datetime-local\" class=\"$classes\" $attrs value=\"$formatted\">";
        // return "<input-datetime class=\"$classes\" $attrs value=\"$formatted\"$pattern></input-datetime>";

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
        $template = $this->getDirective("view");
        if($template) $final = view($template, ['doc' => $this, 'field' => $this->value[0]]);
        else {
            $template = $this->getDirective("template");
            $final = view_from_string($template, ['doc' => $this, 'field' => $this->value[0]]);
        }
        if(!$template) throw new Exception("Cannot create a field for $this->name, must set a 'view' or 'template' directive");
        return "<input-object-array name='$this->name'><template>$final</template><var>".json_encode($this->value)."</var></input-object-array>";
    }

    public function textarea($classes = "", $misc = [], $tag = "textarea") {
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $pattern = $this->getDirective("pattern", false);
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
            'name' => $this->name ?? "",
            'type' => $this->type ?? "",
            'data' => $misc['data'] ?? [],
        ], $misc);
    }

    function getAttribute($attr, $value) {
        return "$attr=\"".htmlspecialchars($value)."\"";
    }

    function getDataAttributes($data) {
        $d = "";
        foreach ($data as $k => $v) {
            $d .= "data-" . htmlspecialchars($k) . "=\"" . htmlspecialchars($v) . "\"";
        }
        return $d;
    }

    // abstract public function getDirective($directiveName, $throwOnFail = false);
}