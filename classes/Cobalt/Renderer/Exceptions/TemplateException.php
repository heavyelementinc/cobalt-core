<?php

namespace Cobalt\Renderer\Exceptions;

use Exception;

class TemplateException extends Exception {

    // protected string $message;
    protected string $templateName;
    protected string $functionName;
    protected string $snippetAtIssue;
    protected string $codeContents;
    protected bool $isTemplate;

    function __construct(
        string $message,
        string $templateName,
        string $functionName,
        string $snippetAtIssue,
          bool $isTemplate
    ) {
        parent::__construct($message, 900);
        // $this->message = $message;
        $this->setFileName($templateName);
        $this->setCodeContents($templateName);
        $this->snippetAtIssue = $snippetAtIssue;
        $this->functionName = $functionName;
        $this->isTemplate = $isTemplate;
    }

    
    public function getCodeContents() {
        return $this->codeContents;
    }

    public function setCodeContents($templateName) {
        if($templateName) {
            return $this->codeContents = file_get_contents($this->templateName);
        }
    }

    public function getSnippetAtIssue() {
        return $this->snippetAtIssue;
    }

    public function setFileName($name):void{
        $this->templateName = $name;
    }

    public function getFileName(): string {
        // if($this->isTemplate) {
            // if(template_exists($this->templateName))
            return $this->templateName;
        // }
        return "";
    }

    public function getTemplateLine(): int {
        $file_content = $this->codeContents;
        $content_before_string = strstr($file_content, $this->snippetAtIssue, true);

        if ($content_before_string !== false) {
            $line = count(explode("\n", $content_before_string));
            return $line;
        }

        return strpos($this->codeContents, $this->snippetAtIssue);
    }

    // public function getMessage(): string {
    //     return $this->message;
    // }

    // public function getCode():int {
    //     return 900;
    // }

    // public function getTrace(): array {
    //     $backtrace = debug_backtrace();
    //     array_push($backtrace, [
    //         'function' => $this->functionName,
    //         'line' => $this->getLine(),
    //         'file' => $this->templateName,
    //         'class' => $this,
    //         'type' => '',
    //         'args' => []
    //     ]);
    //     return $backtrace;
    // }

    // public function getTraceAsString(): string {
    //     return "";
    // }

    // public function getPrevious(): ?Throwable {
    //     return null;
    // }

    // public function __toString() {
    //     return $this->message;
    // }

}