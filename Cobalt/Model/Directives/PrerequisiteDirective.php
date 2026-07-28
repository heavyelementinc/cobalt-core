<?php
namespace Cobalt\Model\Directives;

use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Cobalt\Model\Classes\PrerequisiteTypes;
use Stringable;

class PrerequisiteDirective extends AbstractDirective implements Stringable {
    private string $value = "";

    public function __toString(): string {
        return $this->getValue();
    }
    
    public function reset(array $instructions, $on = "ready,done"):PrerequisiteDirective {
        $instructions = json_encode($instructions);
        $this->value .= "<option on=\"$on\" reset>$instructions</option>";
        return $this;
    }

    public function add(string $query, mixed $value, array $instructions, PrerequisiteTypes $type = PrerequisiteTypes::EQUALS, string $on = "ready,done"):PrerequisiteDirective {
        $instructions = json_encode($instructions);
        $this->value .= "<option on=\"$on\" query=\"$query\" type=\"".$type->value."\" value=\"$value\">$instructions</option>\n";
        return $this;
    }

    public function getValue(): mixed {
        $parent = func_get_arg(0);
        return "<match-update for=\"".$parent->name."\">".$this->value."</match-update>";
    }

    
}