<?php

namespace Controllers\Attributes;

class Header extends Attribute {
    private string $header;

    function __construct(string $header) {
        $this->header = $header;
    }

    public function execute(): void {
        header($this->header);
    }
}