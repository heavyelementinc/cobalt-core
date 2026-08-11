<?php

namespace Cobalt\HTML;

use Cobalt\DataModel\Types\Generic;
use Dom\Element;
use Dom\HTMLDocument;
use Override;
use Stringable;

class Field implements Stringable {
    private readonly HTMLDocument $document;
    public readonly Element $element;
    /**
     * @property array<string,string> $attributes
     */
    protected array $attributes = [];

    function __construct(public readonly Generic $generic, protected string $tag = "input", protected string $type = "text") {
        $this->document = new HTMLDocument();
        $this->element = new Element();
        $this->element->ownerDocument = $this->document;
        $this->element->tagName = $tag;
        switch($tag) {
            case "input":
                $this->element->setAttribute('type', $type);
                break;
        }
    }

    function setId(string $id) {
        $this->element->id = $id;
    }

    function getId():string {
        return $this->element->id;
    }

    function setAttribute(string $name, string $value) {
        $this->element->setAttribute($name, $value);
    }

    function setInnerHTML(string $innerHTML) {
        return $this->element->innerHTML = $innerHTML;
    }

    #[Override]
    public function __toString(): string {
        return $this->render();
    }

    public function render():string {
        $this->element->innerHTML = $this->generic->getFieldInnerHTMLBefore() . $this->element->innerHTML . $this->generic->getFieldInnerHTMLAfter();
        $this->element->setAttribute('name', $this->generic->getFieldDotNotation());
        $this->element->classList->value = $this->generic->directives?->classList->value ?? $this->element->classList->value;
        return $this->document->saveHtml($this->element);
    }
}