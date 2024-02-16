<?php

namespace Cobalt\Renderer;

use Cobalt\Renderer\Exceptions\TemplateException;

class Debugger {
    private TemplateException $exception;
    function __construct(TemplateException $exception) {
        header("500 Server Error");
        $this->exception = $exception;        
    }

    function render() {
        $message = htmlspecialchars($this->exception->getMessage());
        $filename = htmlspecialchars($this->exception->getFileName());
        $line = $this->exception->getTemplateLine();
        $snippet = $this->exception->getSnippetAtIssue();
        $code = htmlspecialchars(str_replace($snippet, "<text color='red'>$snippet</text>", $this->exception->getCodeContents()));

        return "
        <h1>Template Exception</h1>
        <p>$message</p>
        <code>$filename, line $line</code>
        <pre>$code</pre>
        ";
    }
}