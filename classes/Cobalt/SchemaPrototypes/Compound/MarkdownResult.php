<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Prototype;

use Validation\Exceptions\ValidationIssue;

class MarkdownResult extends StringResult {
    protected $type = "string";

    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    
    #[Prototype]
    protected function substring(string $start, ?string $length = null, array $options = []) {
        // Establish our options
        $opts = array_merge([
            'markdown' => false,
            'strip' => false,
        ], $options);

        // Let's get a workable value
        $val = $this->getValue();
        
        // If we're supposed to remove tags, let's do that.
        if($opts['strip']) $val = strip_tags(from_markdown($val, false));
        
        $substr = substr($val,$start, $length);
        if($opts['markdown'] && !$opts['strip']) return from_markdown($substr, $this->asHTML);
        return $substr;
    }

    #[Prototype]
    protected function strip_formatting() {
        return strip_tags(from_markdown($this->getValue(), false));
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], string $tag = ""):string {
        // return $this->markdownarea($class, $misc);
        return "<markdown-area name=\"$this->name\">" . $this->getValue() . "</markdown-area>";
    }
}