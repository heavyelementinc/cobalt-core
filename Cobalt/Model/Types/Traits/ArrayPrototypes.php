<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Attributes\Prototype;
use Iterator;

trait ArrayPrototypes {
    #[Prototype]
    protected function toArray() {
        if(is_array($this->value)) return;
        $this->value = iterator_to_array($this->value ?? []);
    }

    #[Prototype]
    protected function push():int {
        $this->toArray();
        $args = func_get_args();
        return array_push($this->value, ...$args);
    }

    #[Prototype]
    protected function pop():mixed {
        $this->toArray();
        return array_pop($this->value);
    }

    #[Prototype]
    protected function length(): int|null {
        $this->toArray();
        $val = $this->getValue();
        return count($val ?? []);
    }

    #[Prototype]
    function slice(int $from, ?int $length = null, bool $preserve_keys = false) {
        $this->toArray();
        $val = $this->getValue();
        $result = array_slice($val, $from, $length, $preserve_keys);
        $this->raw = $result;
        $this->value = $result;
    }

    #[Prototype]
    function splice(int $from, ?int $length = null, mixed $replacement = []) {
        $this->toArray();
        $val = $this->getValue();
        $result = array_splice($val, $from, $length, $replacement);
        $this->raw = $result;
        $this->value = $val;
    }

    #[Prototype]
    function join($delimiter = null) {
        $delimiter = $delimiter ?? $this->directiveOrNull('delimiter') ?? ", ";
        return implode($delimiter, $this->value);
    }
}