<?php

namespace Cobalt\DBManagement;

use Cobalt\DBManagement\Enums\SortEnum;

class Query {
    private array $filter = [];
    private array $sort = [];
    private int $skip = 0;
    private int $limit = 20;
    private array $projection = [];

    /**
     * This will overwrite the current filter
     * @param array $value 
     * @return Query 
     */
    public function setFilter(array $value = []):Query {
        $this->filter = $value;
        return $this;
    }

    /**
     * This function will append the current fieldnames to the filter
     * @param string $fieldname 
     * @param mixed $value 
     * @return Query 
     */
    public function addFilter(string $fieldname, mixed $value):Query {
        $this->filter[$fieldname] = $value;
        return $this;
    }

    /**
     * 
     * @param int $limit 
     * @return Query 
     */
    public function limit(int $limit):Query {
        $this->limit = $limit;
        return $this;
    }

    public function skip(int $skip):Query {
        $this->skip = $skip;
        return $this;
    }

    public function sort(string $field, SortEnum $sort):Query {
        $this->sort[] = [$field => $sort->value];
        return $this;
    }

    public function projection(array $projection):Query {
        $this->projection = $projection;
        return $this;
    }
}