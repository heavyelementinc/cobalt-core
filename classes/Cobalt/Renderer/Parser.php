<?php

namespace Cobalt\Renderer;

use Cobalt\Renderer\Exceptions\TemplateException;
use PHPUnit\Framework\Constraint\IsInstanceOf;

class Parser {
    protected string $reference;
    protected string $label;
    protected array $labelMap;
    protected string $unparsedArguments;
    protected string|null $filename;
    protected array $vars;
    protected Render $renderer;

    function __construct(
        string $reference, 
        string $label, 
        string $arguments, 
        string|null $filename,
        array &$vars
        // Render $renderer
    ){
        $this->reference = $reference;
        $this->label = $label;
        $this->parseLabel();
        $this->unparsedArguments = $arguments;
        $this->filename = $filename;
        $this->vars = $vars;
        // $this->renderer = $renderer;
    }

    function parseLabel() {
        $this->labelMap = explode(".", $this->label);
    }

    private bool $mustBreak = false;
    private mixed $mutant;
    private string $currentPath = "";

    function lookup(&$vars) {
        $this->mutant = $vars;
        $map = [...$this->labelMap];
        $this->currentPath = "";

        foreach($map as $key) {
            if($this->mustBreak === true) return $this->getLookupValue();
            $this->mustBreak = true;
            $type = gettype($this->mutant);
            switch($type) {
                case "array":
                    $this->getArray($key, $this->mutant);
                    break;
                case "object":
                    $this->getObject($key, $this->mutant);
                    break;
                case "string":
                default:
                    break 2;
            }
            $this->currentPath .= "$key.";
        }

        if($this->currentPath === "$this->label.") return $this->getLookupValue();
        if(__APP_SETTINGS__['RenderV2_throw_template_exception_on_no_value']) {
            throw new TemplateException("Lookup for $this->label failed to find a suitable value", $this->filename, "lookup", $this->reference, ($this->filename === null) ? false : true);
        }
    }

    private function getLookupValue(){
        $value = $this->mutant;
        unset($this->mutant);
        return $value;
    }

    private function getArray($needle, $haystack) {
        if(!key_exists($needle, $haystack)) return $this->mustBreak = true;
        $this->mutant = $haystack[$needle];
        $this->mustBreak = false;
    }

    private function getObject($needle, $haystack) {
        switch(get_class($this->mutant)) {
            case "\\Cobalt\\SchemaPrototypes\\SchemaResult":
                $this->mustBreak = true;
                break;
            case "\\Cobalt\\Customization\\CustomizationManager":
                $this->mutant = $this->mutant->getCustomizationValue($needle);
                $this->currentPath = str_replace("custom.$needle", "value", $this->label);
                break;
            case "\\Cobalt\\Maps\\GenericMap":
            case "\\Validation\\Normalize":
                $temp_path = $this->getTempPath($mutated_path ?? $this->label, $needle);
                if(isset($haystack->{$temp_path})) $this->mutant = $haystack->{$temp_path};
                $this->mustBreak = true;
        }
    }

    private function getTempPath($path, $key) {
        $index = strpos($path, $key);
        $substr = substr($path, $index);
        return $substr;
    }

    public function __toString() {
        return $this->lookup($this->vars);
    }
}