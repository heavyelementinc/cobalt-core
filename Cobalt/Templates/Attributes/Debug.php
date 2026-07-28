<?php
namespace Cobalt\Templates\Attributes;

class Debug {

    function __construct(public string $file, public int $line, public int $column) {
        
    }
}