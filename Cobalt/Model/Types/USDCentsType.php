<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Directive;

class USDCentsType extends NumberType {
    const CONVERSION_FACTOR = 100;
    function display(): mixed {
        $currencyCharacter = $this->directiveOrNull('currencyIndicator') ?? '$';
        return $currencyCharacter . $this->format(true);
    }

    function format($includeThousands = false): mixed {
        return number_format($this->value / self::CONVERSION_FACTOR, 2, ".", ($includeThousands) ? "," : "");
    }

    function filter($value) {
        $val = parent::filter($value);
        return $val * self::CONVERSION_FACTOR;
    }

    public function onUpdateConfirmed($value):void {
        update("[name='".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."']", ['value' => $this->display()]);
    }

    function field(string $class = "", array $misc = [], ?string $tag = null): string {
        return parent::field($class, array_merge($misc, [
            'value' => number_format($this->value / self::CONVERSION_FACTOR, 2, ".", "")
        ]), $tag);
    }

    public function initDirectives(): array {
        return [
            'step' => 0.01
        ];
    }
    
    public function defaultIndexView() {
        return "<code class=\"us-dollar-cents\">".$this->display()."</code>";
    }

    #[Directive]
    protected function currencyIndicator():mixed {
        return "$";
    }
}