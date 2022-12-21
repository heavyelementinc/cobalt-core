<?php
namespace Controllers;

use Clue\StreamFilter\CallbackFilter;
use Drivers\Database;
use Exception;
use Exceptions\HTTP\BadRequest;
use Iterator;
use Render\FlexTable;

class Controller {

    protected $limit = 20;
    protected $limitOverride = false;

    protected $filter = [];
    protected $options = [];
    protected $searchFieldName = false;

    protected $controlMethod = "GET";

    protected $sortDirectionParam = "dir";

    protected $defaultFilter = [
        'key' => 'someKey',
        'type' => 'int',
        // 'callback' => function ($value) {}
    ];

    protected $currentlyAllowedQueryParams = null;


    public function setLimit(int $limit = 20) {
        $this->limit = $limit;
    }

    public function allowLimitOverride(bool $limitOverride = false) {
        $this->limitOverride = $limitOverride;
    }

    public function enableSearchField(string $searchName) {
        $this->searchFieldName = $searchName;
    }

    /**
     * Alias of parseFilterAndOptions
     * @param Database $manager 
     * @param array $filterOverride 
     * @param array $allowedFilters 
     * @param array $allowedOptions 
     * @return array 
     * @throws BadRequest 
     * @throws Exception 
     * @deprecated use ->params instead
     */
    public function getParams(\Drivers\Database &$manager, array $filterOverride, array $allowedFilters = [], $allowedOptions = [], $defaultOptions = null): array {
        return $this->parseFilterAndOptions($manager, $filterOverride, $allowedFilters, $allowedOptions, $defaultOptions);
    }

    public function params(\Drivers\Database &$manager, array $defaultFilters = [], $misc = [
        'defaultOptions' => null,
        'allowedFilters' => [],
        'allowedOptions' => [],
    ]) {
        return $this->parseFilterAndOptions($manager, $defaultFilters, $misc['allowedFilters'] ?? [], $misc['allowedOptions'] ?? [], $misc['defaultOptions'] ?? []);
    }

    /**
     * How to use:
     * 
     * ```
     *  $manager = new SomeDatabaseManager();
     *  $result = $manager->findOne(...$this->parseFilterAndOptions(
     *    $manager,
     *    [
     *        'search' => []
     *    ]
     * ));
     * ```
     * 
     * @param array $allowedFilters 
     * @param array $allowedOptions 
     * @param int $limit 
     * @param bool $limitOverride 
     * @return array 
     * @throws BadRequest 
     * @throws Exception 
     */
    final public function parseFilterAndOptions(\Drivers\Database &$manager, array $filterOverride, array $allowedFilters = [], $allowedOptions = [], $defaultOptions = null): array {
        $this->manager = $manager;
        $this->filterOverride = $filterOverride;
        $this->defaultOptions = $defaultOptions;
        $this->allowedFilters = $allowedFilters;
        $this->allowedOptions = $allowedOptions;
        $this->filter = $this->getFilters($allowedFilters);
        $this->options = $this->getOptions($allowedOptions, $this->defaultOptions);
        return [array_merge($this->filter, $this->filterOverride), $this->options];
    }

    final public function getFilters(array $allowedFilters): array {
        // $result = [];
        
        return $this->parseData($allowedFilters,$_GET);
    }

    final public function getOptions($allowedOptions, $data = []):array {
        if($data === null) $data = [];
        $data = array_merge($data, $_GET);
        // Special case for the 'limit' guy.
        if(key_exists("limit",$data)) $this->limit = (int)$data['limit'];

        $allowed = [
            'page' => [
                'callback' => function ($val) {
                    if(!$val) $val = 1;
                    return [
                        'limit' => $allowedOptions['limit'] ?? (int)$this->limit,
                        'skip' => ($allowedOptions['skip'] ?? $this->limit) * ((int)$val - 1)
                    ];
                }
            ],
            'sort' => [
                'callback' => function ($val) {
                    if(!$val) return [];
                    if(gettype($val) === "array") return ['sort' => $val];
                    $direction = $_GET[$this->sortDirectionParam] ?? 1;
                    $direction = (int)$direction;
                    return [
                        'sort' => [$val => $direction],
                    ];
                }
            ]
        ];
        
        $allowed = array_merge($allowed, $allowedOptions);
        $final = [];

        // Remove limit parameter
        if(key_exists('limit', $allowed)) {
            // We don't want to allow 'limit' to be overridden by a $_GET param unless
            // the code allows it. Or we have root access.
            if(!$this->limitOverride || !is_root()) unset($allowed['limit']);
        }

        $final = array_merge($allowed, $this->options);
        
        return $this->parseData($final, $data);
    }

    public function getPaginationControls($withPageNumber = true) {
        
        $count = $this->manager->count(array_merge($this->filter,$this->filterOverride));
        if($count !== 0 && $this->limit !== 0) {
            $pageCount = ceil(($count / $this->limit));
            // $pageCount = ($pageCount) ? $pageCount : 1;
        }

        $pageCount = $pageCount ?? 1;

        $currentPage = $_GET['page'] ?? 1;

        $previousButton = "<button name='page' value='" . ($currentPage - 1) . "'";
        if($currentPage - 1 <= 0) $previousButton .= " disabled='disabled'";
        $previousButton .= ">&lt;</button>";

        $nextButton = "<button name='page' value='" . ($currentPage + 1) . "'";
        if($currentPage + 1 > $pageCount) $nextButton .= " disabled='disabled'";
        $nextButton .= ">&gt;</button>";

        $pageNumber = "";
        if($withPageNumber) $pageNumber = $currentPage . " / $pageCount";

        $searchField = "";
        if($this->searchFieldName) {
            $populated = ($_GET[$this->searchFieldName]) ? htmlspecialchars($_GET[$this->searchFieldName]) : "";
            $searchField = "<input type='search' name='$this->searchFieldName' value='$populated'>";
        }
        
        return "<form class='cobalt-query-controls' method='".$this->controlMethod."'>$searchField $previousButton $pageNumber $nextButton</form>";
    }

    public function getPaginationLinks($withPageNumber = true) {
        $count = $this->manager->count(array_merge($this->filter,$this->filterOverride));
        if($count !== 0 && $this->limit !== 0) {
            $pageCount = ceil(($count / $this->limit));
        }

        if(!$pageCount) return "";

        $pageCount = $pageCount ?? 1;
        $currentPage = $_GET['page'] ?? 1;

        $previousPageNumber = $currentPage - 1;
        $previousLink = "<a href='?" . $this->paramContinuity(['page' => $previousPageNumber]) . "'";
        if($previousPageNumber <= 0) $previousLink = "<a disabled";
        $previousLink .= " class='page-controls'><i name='chevron-left'></i></a>";

        $nextPageNumber = $currentPage + 1;
        $nextLink = "<a href='?" . $this->paramContinuity(['page' => $nextPageNumber]) . "'";
        if($nextPageNumber > $pageCount) $nextLink = "<a disabled";
        $nextLink .= " class='page-controls'><i name='chevron-right'></i></a>";

        $pageNumber = "";
        if($withPageNumber) $pageNumber = "<span>$currentPage / $pageCount</span>";

        return "<div class='cobalt-query-controls'>$previousLink $pageNumber $nextLink</div>";

    }

    /**
     * 
     * @param mixed $allowedFilters 
     * @param mixed $data 
     * @return array 
     * @throws BadRequest 
     */
    private function parseData($allowedFilters, $data = null):array {
        if($data === null) $data = $_GET;
        $mutant = [];
        foreach($allowedFilters as $key => $values){
            if(!key_exists($key, $data)) {
                if(!in_array($key,["sort","page"])) continue;
            }
            $k = $key;
            // Check if this key wants to write a different parameter
            if(key_exists('key',$values)) $k = $values['key'];
            // Set the new param to the value of $data[$key]
            $mutant[$k] = $data[$key];

            // Let's handle typecasting since $_GET parameters are always strings
            // Now, if the 'type' exists AND the type value doesn't match the value we have
            if(key_exists('type', $values) && gettype($mutant[$k]) !== $values['type']) {
                switch($values['type']) {
                    case "string":
                        $mutant[$k] = (string)$mutant[$k];
                    break;
                    case "int":
                        if(!is_numeric($mutant[$k])) throw new BadRequest("The request has unsupported values.");
                        $mutant[$k] = (int)$mutant[$k];
                    break;
                    case "float":
                        if(!is_numeric($mutant[$key])) throw new BadRequest("The request has unsupported values.");
                        $mutant[$k] = (float)$mutant[$k];
                    break;
                    default:
                        throw new BadRequest("The request has unsupported values.");
                    break;
                }
            }

            // Let's check if we were handed a callback
            if(key_exists('callback',$values) && is_callable($values['callback'])) {
                // Here's where we run the callback
                $m = $values['callback']($mutant[$k],$this);
                switch(gettype($m)) {
                    case "array":
                        // Let's unset the value $mutant[$k] and then merge it with the resulting array
                        unset($mutant[$k]);
                        $mutant = array_merge($mutant, $m);
                        break;
                    default:
                        // Otherwise we just set the value we got from the callback to the $mutant[$k]
                        $mutant[$k] = $m;
                        break;
                }
            }
        }

        $this->getCurrentParams();

        // Return mutant
        return $mutant;
    }

    public function clear_filter($name) {
        unset($this->filterOverride[$name]);
    }

    public function clear_option($name) {
        unset($this->defaultOptions[$name]);
    }

    /**
     * Apply a view to documents
     * 
     * @param array|Iterator $docs - An array or MongoDB Cursor
     * @param string|Closure $view - The path to a view or a function which MUST return a path to a view
     * @param string $root - The name of the variable for referencing in the view template
     * @return string - A string which has concatinated the results of all views
     */
    function docsToViews(array|Iterator $docs, string|\Closure $view, string $root = "doc"): string {
        $views = "";
        foreach($docs as $doc) {
            $views .= view(($view instanceof \Closure) ? $view($doc) : $view, [$root => $doc]);
        }
        return $views;
    }

    function getCurrentParams() {
        $valid_get_params = [];
        foreach(array_merge(array_keys($this->allowedFilters), array_keys($this->allowedOptions)) as $allowed) {
            if(key_exists($allowed, $_GET)) $valid_get_params = array_merge($valid_get_params,[$allowed => $_GET[$allowed]]);
        }
        $this->currentlyAllowedQueryParams = $valid_get_params;
        return $valid_get_params;
    }

    private function paramContinuity($modifiedParams, $currentParams = null) {
        if(!$currentParams) $currentParams = $this->currentlyAllowedQueryParams ?? $this->getCurrentParams();
        return http_build_query(array_merge($currentParams, $modifiedParams));
    }

    /**
     * Provide a layout array which may contain the following keys:
     *   * 'name'     - REQUIRED, the name presented to the user
     *   * 'callback' - A callback which allows the user to modify 
     *   * 'no_link'  - BOOL, disables the link generation for this item
     * 
     * @param array $layout 
     * @param string $container_type 
     * @return string 
     */
    public function sortableTableHeader(array $layout, $container_type = "flex-header") {
        $valid_get_params = $this->currentlyAllowedQueryParams ?? $this->getCurrentParams();

        $result = "";
        foreach($layout as $name => $schema) {
            $icon = "";
            $direction = 1;
            if($_GET['sort'] === $name) {
                $icon = "menu-up";
                if($_GET[$this->sortDirectionParam] === "1") {
                    $direction = -1;
                    $icon = "menu-down";
                }
                $icon = "<i class='sort-icon' name='$icon'>";
            }
            
            if(is_callable($schema['callable'])) $direction = $schema['callable']($name, $direction);

            $new_query_string = $this->paramContinuity(['sort' => $name, $this->sortDirectionParam => $direction]);
            if($schema['no_link']) {
                $result = "<flex-header>$schema[name]</flex-header>";
                continue;
            }
            $result .= "<$container_type><a href=\"?$new_query_string\">".$schema["name"]." $icon</i></a></$container_type>";
        }

        // $display = "";

        // foreach($layout as $name = $schema) {
        //     $display .= "<flex-cell>"
        // }
        return $result;
    }
}
