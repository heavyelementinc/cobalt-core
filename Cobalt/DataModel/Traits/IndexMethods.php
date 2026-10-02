<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Types\DocumentType;
use Cobalt\DataModel\Types\Generic;
use Exceptions\HTTP\BadRequest;

/**
 * @mixin Generic
 */
trait IndexMethods {
    
    private $_indexSortOrder = 0;

    function initIndexItem(array $parameters, DocumentType $document):void {
        if(!$this->directives?->index?->sortable) return;
        $name = $this->name;
        if(!key_exists($name, $parameters['sort'] ?? [])) return;
        $order = clamp((int)$parameters['sort'][$name], -1, 1);
        if($order === 0) throw new BadRequest("Invalid sort");
        $document->registerQuerySortParams($name, $order);
    }

    function getIndexHeader(DocumentType $document):string {
        $name = $this->name;
        $label = $this->directives?->index?->getLabel();
        if(!$this->directives?->index?->sortable) return "<th>$label</th>";
        $order = ($this->_indexSortOrder == 1) ? "asc" : "desc";
        return "<th><a href=\"".$document->getHyperlink($name, $this->_indexSortOrder, true)."\" class=\"index--$order\">$label</a></th>";
    }

    function getIndexCell(string $href):string {
        $label = $this->directives->index->getValue();
        return <<<HTML
        <td class="field--$this->name"><a href="">$label</a></td>
        HTML;
    }

    function getValid():?array {
        if($this->directives->hasDirective('valid')) {
            return $this->directives->valid->normalized();
        }
        return null;
    }

}