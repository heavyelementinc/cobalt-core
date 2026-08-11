<?php

namespace Cobalt\Model\Types\Traits;

trait HtmlSafeable {
    protected bool $asHTML = false;
    
    /**
     * When $enableAsHTML is `false`, htmlspecialchars will be applied
     * to this variable.
     * @param bool $enableAsHTML 
     * @return void 
     */
    public function htmlSafe(bool $enableAsHTML) {
        $this->asHTML = $enableAsHTML;
    }
}