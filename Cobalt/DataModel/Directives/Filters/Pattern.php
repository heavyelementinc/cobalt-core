<?php

namespace Cobalt\DataModel\Directives\Filters;

use Attribute;
use Cobalt\DataModel\Directives\Base\AbstractStringDirective;
use Override;
use TypeError;

#[Attribute()]
class Pattern extends AbstractStringDirective {
    function __construct(string $value, private ?string $failureMessage = null, bool $isMethod = false) {
        return parent::__construct($value, $isMethod);
    }
    /**
     * @param string $value 
     * @return void 
     */
    #[Override]
    public function setValue(mixed $value): void {
        if(preg_match($value, "") === false) throw new TypeError(sprintf("Failed to configure Pattern for %s. Is the pattern valid regex?", $this->type->name));
        $this->value = $value;
    }

    public function getFailureMessage():string {
        return $this->failureMessage ?? "Failed to match the given pattern";
    }
}