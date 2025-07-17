<?php

namespace Cobalt\Model\Types;

class USDCentsType extends NumberType {
    const CONVERSION_FACTOR = 100;
    function display(): mixed {
        return number_format($this->value / self::CONVERSION_FACTOR, 2);
    }

    function filter($value) {
        $val = parent::filter($value);
        return $val * self::CONVERSION_FACTOR;
    }

    public function onUpdateConfirmed($value):void {
        update("[name='".$this->{MODEL_RESERVERED_FIELD__FIELDNAME}."']", ['value' => $this->display()]);
    }

    function field(string $class = "", array $misc = [], ?string $tag = null): string {
        return parent::field($class, array_merge($misc, ['value' => $this->display()]), $tag);
    }

    public function initDirectives(): array {
        return [
            'step' => 0.01
        ];
    }
}