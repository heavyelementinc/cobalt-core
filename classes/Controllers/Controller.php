<?php
namespace Controllers;

use Exception;
use Exceptions\HTTP\BadRequest;

class Controller{

    protected $limit = 20;
    protected $limitOverride = false;

    protected $filter = [];
    protected $options = [];
    protected $searchFieldName = false;

    protected $controlMethod = "GET";

    protected $defaultFilter = [
        'key' => 'someKey',
        'type' => 'int',
        // 'callback' => function ($value) {}
    ];


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
    final public function parseFilterAndOptions(\Drivers\Database &$manager, $filterOverride, array $allowedFilters = [], $allowedOptions = []): array {
        $this->manager = $manager;
        $this->filter = $this->getFilters($allowedFilters);
        $this->options = $this->getOptions($allowedOptions);
        $this->filterOverride = $filterOverride;
        return [array_merge($this->filter, $filterOverride), $this->options];
    }

    final public function getFilters(array $allowedFilters): array {
        // $result = [];
        
        return $this->parseData($allowedFilters,$_GET);
    }

    final public function getOptions($allowedOptions, $data = null):array {
        if($data === null) $data = $_GET;
        $allowed = [
            'page' => [
                'callback' => function ($val) {
                    if(!$val) $val = 1;
                    return [
                        'limit' => (int)$this->limit,
                        'skip' => $this->limit * ((int)$val - 1)
                    ];
                }
            ],
            'sort' => [
                'callback' => function ($val) {
                    return [
                        'sort' => $val
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
        
        return $this->parseData($final,$data);
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

    private function parseData($allowedFilters, $data = null):array {
        if($data === null) $data = $_GET;
        $mutant = [];
        foreach($allowedFilters as $key => $values){
            if(!key_exists($key, $data)) {
                if(!in_array($key,["sort","page"])) continue;
            }
            $k = $key;
            if(key_exists('key',$values)) $k = $values['key'];
            $mutant[$k] = $data[$key];
            if(key_exists('type', $values)) {
                if(gettype($mutant[$k]) !== $values['type']) {
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
            }

            if(key_exists('callback',$values) && is_callable($values['callback'])) {
                // Here's where we run the callback
                $m = $values['callback']($mutant[$k]);
                switch(gettype($m)) {
                    case "array":
                        $mutant = array_merge($mutant, $m);
                        break;
                    default:
                        $mutant[$k] = $m;
                        break;
                }
            }
        }
        return $mutant;
    }
}