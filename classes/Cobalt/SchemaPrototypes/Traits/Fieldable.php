<?php

namespace Cobalt\SchemaPrototypes\Traits;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Prototype;

trait Fieldable {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], string $tag = "input"):string {
        return $this->input($class, $misc, $tag);
    }

    // #[Prototype]
    // abstract protected function field(string $class = "", array $misc = [], string $tag = ""):string;

    /**
     * The field method returns an editable field
     */
    protected function input($classes = "", $misc = [], $tag = "input"):string {
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $closingTag = "";
        if($tag !== "input") $closingTag = "</$tag>";
        
        if($this->getDirective("private")) return "";
        if($this->getDirective("immutable")) $misc['readonly'] = 'readonly';
        
        $value = $this->getValue();
        $pattern = $this->getDirective("pattern", false);
        if($pattern) $pattern = " pattern=\"".htmlentities($pattern)."\"";
        return "<$tag class=\"$classes\" $attrs value=\"" . htmlspecialchars($value) . "\"$pattern>$closingTag";
    }

    protected function inputDate($classes = "", $misc = []) {
        $misc = array_merge([
            'from' => $this->schema['from'] ?? "ISO 8601",
            'to'   => $this->schema['to'] ?? "ISO 8601",
        ], $misc);
        [$misc, $attrs] = $this->defaultFieldData($misc);
        
        $fmt = "c";
        switch($misc['from']) {
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

        $pattern = $this->getDirective("pattern", false);
        if($pattern) $pattern = " pattern=\"".htmlentities($pattern)."\"";

        return "<input-datetime class=\"$classes\" $attrs value=\"$formatted\"$pattern></input-date>";
    }

    protected function select($classes = "", $misc = [], $tag = "select") {
        [$misc, $attrs] = $this->defaultFieldData($misc);
        
        return "<$tag class=\"$classes\" $attrs>".$this->options()."</$tag>";
    }

    protected function inputAutocomplete($classes = "", $misc = []) {
        return $this->select($classes, $misc, "input-autocomplete");
    }

    protected function inputBinary($classes = "", $misc = []) {
        return $this->select($classes, $misc, "input-binary");
    }

    protected function inputArray($classes = "", $misc = []) {
        return $this->select($classes, $misc, "input-array");
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