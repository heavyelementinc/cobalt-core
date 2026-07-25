<?php

namespace Cobalt\Model\Directives;

use Cobalt\Model\Directives\Abstracts\AbstractDirective;
use Cobalt\Model\Enums\SearchableTypes;

class SearchableDirective extends AbstractDirective {
    private bool $_isSearchable = false;
    private SearchableTypes $_searchType = SearchableTypes::TEXT;
    function __construct(bool $isSearchable, SearchableTypes $searchableType = SearchableTypes::TEXT){
        $this->isSearchable($isSearchable);
        $this->setSearchType($searchableType);
    }
    public function getValue(): mixed {
        return $this->_isSearchable;
    }
    public function isSearchable(bool $value) {
        $this->_isSearchable = $value;
    }

    public function getSearchType():string {
        switch($this->_searchType) {
            case SearchableTypes::TEXT:
            default:
                return "text";
        }
    }
    public function setSearchType(SearchableTypes $type) {
        $this->_searchType = $type;
    }
    
}