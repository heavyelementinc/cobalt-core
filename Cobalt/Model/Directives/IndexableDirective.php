<?php

namespace Cobalt\Model\Directives;

use Closure;
use Error;
use ReflectionFunction;

/**
 * * `title`      - Determines the name of the index column
 * * `view`       - (string) The view for each cell in the index table
 * * `filterable` - (bool: false) Is this index is filterable
 * * `alignment`  - The text justification
 * * `sort`       - Determines default sort order for field
 * * `order`      - Determines the presentation order of this field
 * @package Cobalt\Model\Directives
 */

class IndexableDirective extends GenericDirective {
    private null|string|Closure $title;
    private string|Closure $view;
    private bool|Closure $filterable;
    private string|Closure $alignment;
    private int|Closure $sort;
    private int|Closure $order;
    function __construct(null|string|Closure $title = null,
        null|string|Closure $view = null,
        bool|Closure $filterable = false,
        string|Closure $alignment = "left",
        int|Closure $sort = 0,
        int|Closure $order = 0
    ) {
        // parent::__construct($closure);
        $this->set_title($title ?? $this->_reference->fieldName);
        $this->set_view($view);
        $this->set_filterable($filterable);
        $this->set_alignment($alignment);
        $this->set_sort($sort);
        $this->set_order($order);
    }

    function set_title(null|string|Closure $title) {
        $this->title = $title;
    }
    function set_view (string|Closure $view) {
        $this->view = $view;
    }
    function set_filterable (bool|Closure $filterable) {
        $this->filterable = $filterable;
    }
    function set_alignment (string|Closure $alignment) {
        $this->alignment = $alignment;
    }
    function set_sort (int|Closure $sort) {
        $this->sort = $sort;
    }
    function set_order (int|Closure $order) {
        $this->order = $order;
    }

    function get_title(string|Closure $title):string|Closure {
        if($this->title instanceof Closure == false) return $this->title;
        $result = call_user_func($this->title, ...func_get_args());
        return $result;
    }
    function get_view (string|Closure $view):string|Closure {
        if($this->view instanceof Closure == false) return $this->view;
        $result = call_user_func($this->view, ...func_get_args());
        return $result;
    }
    function get_filterable (bool|Closure $filterable):bool|Closure {
        if($this->filterable instanceof Closure == false) return $this->filterable;
        $result = call_user_func($this->filterable, ...func_get_args());
        return $result;
    }
    function get_alignment (string|Closure $alignment):string|Closure {
        if($this->alignment instanceof Closure == false) return $this->alignment;
        $result = call_user_func($this->alignment, ...func_get_args());
        return $result;
    }
    function get_sort (int|Closure $sort):int|Closure {
        if($this->sort instanceof Closure == false) return $this->sort;
        $result = call_user_func($this->sort, ...func_get_args());
        return $result;
    }
    function get_order (int|Closure $order):int|Closure {
        if($this->order instanceof Closure == false) return $this->order;
        $result = call_user_func($this->order, ...func_get_args());
        return $result;
    }
}