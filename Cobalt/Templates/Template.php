<?php

namespace Cobalt\Templates\Template;

class Template {
    private array $vars = [];
    private bool $hasVars = false;
    private ?string $path = null;
    private bool $hasTemplate = false;

    function setVars(array $vars):void {
        $this->vars = $vars;
    }

    function setPath(string $path):void {
        $candidate = find_one_file(
            [
                // Test if the current app has the template
                __APP_ROOT__,
                __APP_ROOT__ . "/templates/",
                // Check if the environment has the template
                __ENV_ROOT__,
                __ENV_ROOT__ . "/templates/"
            ],
            $path
        );
        if($candidate === false) throw new Error("Template not found.");
        $this->path = $candidate;
        $this->hasTemplate = true;
    }

    function execute():TemplateResult {
        $this->result = new TemplateResult();
        // $this->result
    }

    static function view(string $path, array $vars = []):TemplateResult {
        $process = new static();
        $process->setPath($path);
        $process->setVars($vars);
        return $this->execute();
    }

    static function fromString(string $content, array $vars = []) {

    }
} 