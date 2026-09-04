<?php

namespace Cobalt\Templates\Classes;

use Cobalt\Templates\Compiler;
use Cobalt\Templates\Template\Template;

class TemplateResult {
    readonly Template $template;
    private Compiler $compiler;
    private string $result;
    private bool $done;

    function __construct(
        private string $body,
        private array $variables,
        private ?string $templatePath = null,
    ) {
        $this->compiler = new Compiler();
    }

    function execute() {
        // if(isset($this->templatePath)) 
    }

    function getResult():string {

    }
}