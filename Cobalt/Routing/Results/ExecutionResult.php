<?php

namespace Cobalt\Routing\Results;

use Cobalt\Routing\Interfaces\ExecutionResult as InterfacesExecutionResult;
use Override;
use Stringable;

class ExecutionResult implements InterfacesExecutionResult {

    public private(set) ?string $bodyTemplate = null;
    /**
     * @var array<string,string>
     */
    public private(set) array $replacementValues = [];
    
    public function getBodyView(array $vars = []): string {
        $view = view($this->bodyTemplate, $vars);
        return str_replace(
            array_keys($this->replacementValues), 
            array_values($this->replacementValues),
            $view
        );
    }

    #[Override]
    public function getBodyTemplate(): ?string {
        return $this->bodyTemplate;
    }

    #[Override]
    public function setBodyTemplate(?string $bodyTemplate):void{
        $this->bodyTemplate = $bodyTemplate;
    }

    #[Override]
    public function setControllerResult(string $target, string|Stringable $result): void {
        $this->replacementValues[$target] = (string)$result;
    }

    #[Override]
    public function getControllerResult(): string {
        return $this->replacementValues['@main_content@'] ?? "";
    }
}
