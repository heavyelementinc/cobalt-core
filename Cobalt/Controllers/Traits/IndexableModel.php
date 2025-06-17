<?php

namespace Cobalt\Controllers\Traits;

use Cobalt\SchemaPrototypes\Basic\Anchor;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use Cobalt\Model\Types\MixedType;
use Cobalt\SchemaPrototypes\SchemaResult;
use Exception;
use Exceptions\HTTP\Error;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\Persistable;
use MongoDB\BSON\Regex;
use MongoDB\Database;
use MongoDB\Model\BSONDocument;
use \MongoDB\Driver\Exception\CommandException;
use TypeError;

/** An "index" is the landing page that shows a table of available database entries.
 * To define what fields of a DB entry are shown in the index, provide an 'index'
 * directive in the schema for each field.
 * 
 * Valid keys for the index directive are: `title`, `order`, and `sort`
 * 
 * `title` is the name displayed in the index's header (defaults to `field` if none provided)
 * `order` is the order in which the field should appear in the index table from left to right, (defaults to 0)
 * `sort`  defines the default sorting behavior for this field, defaults to 0 (must be either 1, -1, or 0)
 * `link`  if it's anything other than false, then the cell's value will be wrapped in a link
 * 
 */
trait IndexableModel {
    
    // public Database $manager;
    protected Model $schema;
    protected array $indexableSchema;
    protected array $sortedTable;
    protected bool $hasFilterParamsBeenMerged = false;
    protected array $filterParameters = [];
    protected array $queryParameters = [];

    protected array $searchableFields = [];
    protected array $filterableFields = [];

    public function init(Model $model, array $params) {
        $this->schema = $model;
        $this->indexableSchema = $model->defineSchema();

        $table = [];

        $order = 0;
        foreach($this->indexableSchema as $field => $directives) {
            $order += 1;
            $candidate = $this->getIndexDefinition($field, $directives);
            if(!$candidate) continue;
            $table[$field] = $candidate;
        }

        // Sanity check to ensure we're not moving forward with an empty table
        if(empty($table)) throw new Error("Model is missing indexable directives",true);

        // Sort our table
        usort($table, function ($a, $b) {
            if($a['order'] == $b['order']) return 0;
            ($a['order'] < $b['order']) ? -1 : 1;
        });

        $this->sortedTable = $table;
        $this->param_sanity_check($params);
    }

    public function get_persistent_query_params($as_array = false):string|array {
        // if(!$this->queryParameters['sort']) return "";
        $arr = [
            QUERY_PARAM_SORT_NAME => $_GET[QUERY_PARAM_SORT_NAME],
            QUERY_PARAM_SORT_DIR => $_GET[QUERY_PARAM_SORT_DIR]
        ];
        if(isset($_GET[QUERY_PARAM_SEARCH])) $arr[QUERY_PARAM_SEARCH] = $_GET[QUERY_PARAM_SEARCH];
        if($as_array == true) return $arr;
        return http_build_query($arr);
    }

    public function get_table_header() {
        $safe_get_params = [];
        // Let's make our GET paramters safe to embed in the page
        foreach($_GET as $key => $value) {
            if($key === "uri") continue;
            $safe_get_params[urlencode($key)] = urlencode($value);
        }
        // Establish our table header
        $html = "<flex-row>";
        if($this->schema->__get_index_checkbox_state()) {
            $html .= "<flex-header class=\"doc_id_mark\"><input type=\"checkbox\"></flex-header>";
        }
        foreach($this->sortedTable as $field) {
            // Merge the newly-safe params with the params for this field
            $sort_direction  = 1;
            $classes = "";

            // if($_GET[QUERY_PARAM_SORT_NAME] == $field['name']) {
            if(isset($this->queryParameters['sort'][$field['name']])) {
                $sort_val = $this->queryParameters['sort'][$field['name']];
                $sort_direction = match($sort_val) {
                    null, 0, "0", -1, "-1" => 1,
                    1, "1" => -1,
                };
                // Check if this field is the one being sorted by the get peram and
                // assign the appropriate class so we can communicate the sorting
                // of this value field to the client
                $classes = ($sort_val === -1) ? "sort-desc" : "sort-asc";
            }
            
            $href_params = array_merge($safe_get_params, [QUERY_PARAM_SORT_NAME => urlencode($field['name']), QUERY_PARAM_SORT_DIR => urlencode($sort_direction)]);
            $href = "?" . http_build_query($href_params);
            
            $html .= "<flex-header class=\"$classes\"><a href=\"$href\">".htmlspecialchars($field['title'])."</a></flex-header>";
        }
        return $html . "</flex-row>";
    }

    /** Override this in your controller to set a default query for your index */
    public function index_query():array {
        return [];
    }

    private function __index_query():array {
        if($this->hasFilterParamsBeenMerged) return $this->filterParameters;
        $this->hasFilterParamsBeenMerged = true;

        // Let's check if the filter param is set
        $name = $_GET[QUERY_PARAM_FILTER_NAME];
        $search = $_GET[QUERY_PARAM_SEARCH];

        // Return the 'default query' if it's not set
        if(isset($name)) {
            // Return the 'default query' if it's not set
            if(isset($name)) {
                $fieldNames = explode(",", $_GET[QUERY_PARAM_FILTER_NAME]);
                $values = explode(",", $_GET[QUERY_PARAM_FILTER_VALUE]);
                foreach($fieldNames as $index => $fieldName) {
                    // Let's typecast our query param (this is in case we have an ObjectId or an integer in the URL)
                    // We also want to override the "default" query with filter field
                    $this->add_filter_params([
                        $fieldName => $this->schema->{$fieldName}->typecast($values[$index], $fieldName, QUERY_TYPE_CAST_LOOKUP)
                    ]);
                }
            }
        }
        if(isset($search) && $search) {
            $searchOptions = [
                // 'numericOrdering' => true,
            ];
            // if($_GET[QUERY_PARAM_SEARCH_CASE_SENSITVE] === 'on') $searchOptions['caseLevel'] = true;
            // if(isset($_GET[QUERY_PARAM_COMPARISON_STRENGTH])) {
            //     $searchOptions['strength'] = match($_GET[QUERY_PARAM_COMPARISON_STRENGTH]) {
            //         "2" => 2,
            //         "3" => 3,
            //         default => 1
            //     };
            // }
            $searchQuery = [];
            $searchOptions = [];
            $searchableFields = [];
            $searchableOptions = [];
            $this->getSearchQuery($search, $searchQuery, $searchOptions, $searchableFields, $searchableOptions);
            try {
                if(key_exists('$text', $searchQuery)) {
                    $this->schema->createIndex($searchableFields, []);
                }
            } catch (CommandException $e) {
                $indexes = $this->schema->collection->listIndexes();
                foreach($indexes as $index) {
                    $name = $index->getName();
                    if($name === "_id_") continue;
                    $this->schema->collection->dropIndex($name);
                }
                $this->schema->createIndex($searchableFields, []);
            }
            $this->add_filter_params($searchQuery);
            $this->add_query_params($searchOptions);
        }
        
        return array_merge($this->index_query(), $this->filterParameters);
    }

    private function getSearchQuery(string &$searchParam, array &$searchQuery, array &$searchOptions, array &$searchableFields, array &$searchableOptions) {
        $this->parseSearchParam($searchParam, $searchQuery, $searchOptions, $searchableFields, $searchableOptions);
    }

    private function parseSearchParam(
        string &$searchParam,
        array &$searchQuery,
        array &$searchOptions,
        array &$searchableFields,
        array &$searchableOptions
    ):void {
        if($searchParam[0] !== QUERY_PARAM_SEARCH_FIELD_TOKEN) {
            // If there are no fields specified in the user query, let's use the
            // searchable fields from the model
            $searchableFields = $this->searchableFields;
            $searchQuery = ['$text' => [
                '$search' => $searchParam
            ]];
            $searchOptions = [
                'projection' => [
                    QUERY_SEARCH_MATCH_SCORE_FIELD => ['$meta' => 'textScore']
                ],
                'sort' => [
                    QUERY_SEARCH_MATCH_SCORE_FIELD => ['$meta' => 'textScore']
                ]
            ];
            return;
        }
        // Let's assume we've been sent a search string with multiple fields. It would look like this
        // @field:value:somevalue,@field2.child:value2,@field3:value
        $fields = explode(QUERY_PARAM_SEARCH_DELIMITER_TOKEN . QUERY_PARAM_SEARCH_FIELD_TOKEN, substr($searchParam,1));
        // Fields will now be an array of this structure:
        // ['field:value:somevalue', 'field2.child:value2', 'field3:value']
        foreach($fields as $f) {
            // Iteration 0 through this loop will result in $f looking like this:
            // 'field:value:somevalue'
            $firstIndexOfToken = strpos($f, QUERY_PARAM_SEARCH_VALUE_TOKEN);
            $field = substr($f, 0, $firstIndexOfToken);
            $value = substr($f, $firstIndexOfToken + 1);
            // $search[$field] = $value;
            $this->parseSearchValue($value, $field, $searchQuery, $searchOptions, $searchableFields, $searchableOptions);
            // After the above instruction, we should have an array that looks like this:
            // $search = ["field" => "value:somevalue"];
        }
        if(!empty($searchableFields)) {
            $searchQuery = ['$text' => [
                '$search' => $searchParam
            ]];
            $searchOptions = [
                'projection' => [
                    QUERY_SEARCH_MATCH_SCORE_FIELD => ['$meta' => 'textScore']
                ],
                'sort' => [
                    QUERY_SEARCH_MATCH_SCORE_FIELD => ['$meta' => 'textScore']
                ]
            ];
        }
    }

    private function parseSearchValue(
        string &$searchParam,
        string &$fieldName,
        array &$searchQuery,
        array &$searchOptions,
        array &$searchableFields,
        array &$searchableOptions
    ):void {
        $regexStartEndDelimiter = "/";
        $validRegexTerminationChars = [
            $regexStartEndDelimiter,
            REGEXP_CASE_INSENSITIVE,
            REGEXP_MULTILINE_START_END,
            REGEXP_EXTENDED_IGNORE_WHITESPACE,
            REGEXP_MATCH_NEW_LINE_SPACE_CHAR,
            REGEXP_UNICODE_SUPPORT,
        ];
        $finalSearchParamChar = $searchParam[strlen($searchParam) - 1];
        // Check if the first character of the search param is a '/'
        if($searchParam[0] !== $regexStartEndDelimiter
            // Check if the last character of the search param is a valid final character for a regex
            && !in_array($finalSearchParamChar, $validRegexTerminationChars)
        ) {
            // $searchQuery[$fieldName] = $searchParam;
            if(!key_exists($fieldName, $this->searchableFields) 
            && !has_permission("Model_advanced_search_permission", null, null, false)) {
                throw new Unauthorized("`$fieldName` is outside of model's searchable fields", "Unauthorized");
            }
            $searchableFields[$fieldName] = "text";
            return;
        }
        if(!key_exists($fieldName, $this->searchableFields) 
            && !has_permission("Model_advanced_search_permission", null, null, false)
        ) {
            throw new Unauthorized("`$fieldName` is outside of model's searchable fields", "Unauthorized");
        }
        $regexFlags = "";
        $finalSearchParamDelimiterPosition = strrpos($searchParam, $regexStartEndDelimiter);
        if($finalSearchParamChar !== $regexStartEndDelimiter) {
            $regexFlags = substr($searchParam, $finalSearchParamDelimiterPosition + 1);
            $searchParam = substr($searchParam, 0, $finalSearchParamDelimiterPosition + 1);
        }
        $searchQuery[$fieldName] = new Regex(substr($searchParam,1,-1), $regexFlags);
    }

    public function add_filter_params(array $params) {
        $this->add_params($this->filterParameters, $params);
    }

    public function add_query_params(array $params) {
        $this->add_params($this->queryParameters, $params);
    }

    private function add_params(array &$target, $params) {
        $target = array_merge_recursive($target, $params);
    }

    /** Override this in your controller to set default query options for your index */
    public function index_options():array {
        return ['sort' => ['_id' => -1]];
    }

    public function __index_options():array {
        return array_merge($this->index_options(), $this->queryParameters);
    }

    public function get_table_body() {
        $result = $this->schema->find($this->__index_query(), $this->__index_options());
        $count = 0;
        $html = "";
        foreach($result as $doc) {
            // if($doc instanceof $this->model === false) throw new TypeError("Document must be of type `".$this->model::class."`, found `".$doc::class."` instead.");//$doc = $this->get_schema($doc);
            $this->get_table_row($doc, $html);
            $count += 1;
        }
        set("total_document_count", $count);
        if(!$html) $html = "<flex-row class=\"flex-table--no-results\"><flex-cell col-span=\"".count($this->sortedTable)."\">No results</flex-cell></flex-row>";
        return $html;
    }

    /**
     * 
     * @param GenericModel $doc 
     * @return array{string:row_class,string:row_style,bool:checkbox_checked,bool:checkbox_disabled}
     */
    function getRowDetails(GenericModel $doc):array {
        return [];
    }
    /**
     * 
     * @param Model $doc 
     * @param mixed &$html 
     * @return void 
     * @throws Exception 
     */
    function get_table_row(GenericModel $doc, &$html) {
        $row_details = $this->getRowDetails($doc);
        $row_class = (isset($row_details['row_class'])) ? " class=\"$row_details[row_class]\"" : "";
        $row_style = (isset($row_details['row_style'])) ? " style=\"$row_details[row_style]\"" : "";
        $html .= "<flex-row$row_class"."$row_style>";

        if($this->schema->__get_index_checkbox_state()) {
            $checked = ($row_details['checkbox_checked']) ? " checked=\"checked\"" : "";
            $disabled = ($row_details['checkbox_disabled']) ? " disabled=\"disabled\"" : "";
            $html .= "<flex-cell class=\"doc_id_mark\"><input type=\"checkbox\" name=\"_id\"$checked value=\"$doc->_id\"$disabled></flex-cell>";
        }
        $route = route("$this->name@__edit", [$doc->_id]);
        // Get each cell's contents
        foreach($this->sortedTable as $cell) {
            $mutableRoute = $route;
            $html .= "<flex-cell>";
            
            // $view = $cell['view'];
            // Check if "view" is callable, if it is, let's use the result of that function
            // as what we display for this cell
            // if(!is_string($view) && is_callable($view)) $view = $view($doc[$cell['name']], $doc);
            // If view is not set at all at this point, what should we do?

            // Let's extract the view for this title
            $schema = $doc->readSchema();
            
            if(isset($schema[$cell['name']]['index']['view'])) {
                $view = $schema[$cell['name']]['index']['view'];
                if(!is_string($view) && is_callable($view)) $view = $view($mutableRoute, $doc[$cell['name']], $doc);
                
                if(!$view && method_exists($doc->{$cell['name']}, '__defaultIndexPresentation')) {
                    $view = $doc->{$cell['name']}->__defaultIndexPresentation();
                }
                if(!$view) $doc->{$cell['name']}->display();
            } else if (method_exists($doc->{$cell['name']}, '__defaultIndexPresentation')) {
                $view = $doc->{$cell['name']}->__defaultIndexPresentation();
            } else {
                $view = $doc->{$cell['name']};
            }
            // Let's establish our open/close tags
            $open = "";
            $close = "";
            if($cell['link'] !== false) {
                $open = "<a href=\"$mutableRoute\">";
                $close = "</a>";
            }
            $html .= $open . $view . $close . "</flex-cell>";
        }
        $html .= "</flex-row>";
    }

    
    public function get_hypermedia():array {
        $count = $this->schema->count($this->__index_query(), $this->index_options());

        $limit = $this->queryParameters['limit'];
        
        $total_pages = 0;
        if($count && $limit) $total_pages = ceil($count / $limit);

        // Check if we've got our current page set
        $get_page = $_GET[QUERY_PARAM_PAGE_NUM] ?? 1;
        if(!is_numeric($get_page)) {
            unset($_GET['uri']);
            unset($_GET[QUERY_PARAM_PAGE_NUM]);
            redirect("?". http_build_query($_GET));
            exit;
        }
        // Set the current page number
        $current_page = clamp($get_page, 0, $total_pages);
        $nx_page = $current_page + 1;
        $prev_page = $current_page - 1;

        $next = new Anchor();
        if($nx_page > $total_pages) {
            $next->setDisabled(true);
            $nx_page = $total_pages;
        }
        $next->setClass("hypermedia--page-select hypermedia--next-page");
        $next->setHref("?" . http_build_query(array_merge($_GET, [QUERY_PARAM_PAGE_NUM => $nx_page])));
        $next->setText("<i name=\"chevron-right\"></i>");
        
        $prev = new Anchor();
        if($prev_page < 1) {
            $prev->setDisabled(true);
            $prev_page = 1;
        }
        $prev->setClass("hypermedia--page-select hypermedia--prev-page");
        $prev->setHref("?" . http_build_query(array_merge($_GET, [QUERY_PARAM_PAGE_NUM => $prev_page])));
        $prev->setText("<i name=\"chevron-left\"></i>");

        $multidelete_button = "";
        $filterable_content = "";
        if($this->schema->__get_index_checkbox_state()) {
            $multidelete_button = "<async-button type=\"batch-action\" method=\"DELETE\" action=\"".route(self::className()."@__archive_batch")."\" title=\"Archive\" native><i name=\"archive\"></i></async-button> <async-button type=\"multidelete\" method=\"DELETE\" action=\"".route(self::className()."@__multidestroy")."\" native><i name=\"delete\"></i></async-button>";
            $filterable_content = "<form><label><input type=\"checkbox\"".((filter_var($_GET[QUERY_PARAM_ARCHIVED_DISPLAY], FILTER_VALIDATE_BOOL)) ? "checked=\"checked\"" : "")." name=\"".QUERY_PARAM_ARCHIVED_DISPLAY."\" onchange='submit()'> Show Archived</label></form>";

        }

        return [
            'previous_page' => $prev,
            'next_page' => $next,
            'page' => $current_page,
            'total_pages' => $total_pages,
            'count' => $count,
            'search' => $this->get_search_field(),
            'multidelete_button' => $multidelete_button,
            'filters' => "<inline-menu icon=\"filter-variant\">$filterable_content".implode(" ",$this->filterableFields) ."</inline-menu>",
        ];
    }

    final protected function get_search_field() {
        QUERY_PARAM_COMPARISON_STRENGTH;

        return "<input type=\"search\" name=\"".QUERY_PARAM_SEARCH."\" value=\"".htmlspecialchars($_GET[QUERY_PARAM_SEARCH])."\" placeholder=\"Search\">
        <button type=\"submit\" native><i name=\"magnify\"></i></button>
        <inline-menu>
            <label><input type=\"checkbox\" name=\"".QUERY_PARAM_SEARCH_CASE_SENSITVE."\"".(($_GET[QUERY_PARAM_SEARCH_CASE_SENSITVE] === 'on') ? " checked=\"checked\"": "")."> Case Sensitve</label>
        </inline-menu>
        ";
    }

    /** All you need in order to have a field be included in the index is to include
     * the 'index' key. Things will be inherited 
     */
    final protected function getIndexDefinition(string $field, $directives):?array {
        if($directives instanceof MixedType) {
            return null;
        }
        if(!key_exists('index', $directives)) return null;
        $array = [
            'name' => $field,
            'title' => $this->get_title($field, $directives),
            'order' => $this->get_order($field, $directives),
            'sort' => $this->get_sort($field, $directives),
            'view' => $this->get_view($field, $directives),
            'searchable' => $this->get_searchable($field, $directives),
            'filterable' => $this->get_filterable($field, $directives),
        ];
        return $array;
    }

    final protected function get_title(string $field, array $directives) {
        $index = $directives['index'] ?? [];
        $title = $index['title'] ?? $field;
        if(gettype($title) !== "string" && is_callable($title)) return $title($field, $directives);
        return $title;
    }

    /** The ORDER determines the presentation order of the table */
    final protected function get_order(string $field, array $directives) {
        $index = $directives['index'] ?? [];
        $order = $index['order'] ?? 0;
        if(is_callable($order)) return $order($field, $directives);
        return $order;
    }

    /** The SORT determines the default sort direction for this column (0 means no default sort) */
    final protected function get_sort(string $field, array $directives) {
        $index = $directives['index'] ?? [];
        $sort = $index['sort'] ?? 0;
        if(is_callable($sort)) return $sort($field, $directives);
        return $sort;
    }

    final protected function get_view(string $field, array $directives) {
        $index = $directives['index'] ?? [];
        $sort = $index['view'] ?? null;
        if(is_callable($sort)) return $sort($field, $directives);
        return $sort;
    }

    final protected function get_searchable(string $field, array $directives) {
        $index = $directives['index'] ?? [];
        $searchable = $index['searchable'] ?? false;
        if(is_callable($searchable)) $searchable = $searchable($field, $directives);
        if($searchable === true) $this->searchableFields[$field] = "text";
        return $searchable;
    }

    final protected function get_filterable(string $field, array $directives) {
        $index = $directives['index'] ?? [];
        $filterable = $index['filterable'] ?? false;
        if(is_callable($filterable)) $filterable = $filterable($field, $directives);

        if($filterable === true) {
            $this->filterableFields[$field] = $this->schema->{$field}
                ->get_filter_field($_GET[QUERY_PARAM_FILTER_VALUE], $_GET[QUERY_PARAM_FILTER_NAME], QUERY_TYPE_CAST_OPTION);
        }
        return $filterable;
    }


    // Takes in the $_GET params and combines them with the default find options
    protected function param_sanity_check(array $params) {
    
        $options = [];
        // Our sort model is that the schema should always define the default sort
        foreach($this->sortedTable as $field) {
            // Let's establish our default sort model
            if($field['sort'] === -1 || $field['sort'] === 1) {
                $options['sort'][$field['name']] = $field['sort'];
            }
        }

        // Check if the request asks for a sorting override
        if(key_exists(QUERY_PARAM_SORT_DIR, $params)) {
            $sort_direction = $params[QUERY_PARAM_SORT_DIR];
            if(!$sort_direction) $sort_direction = 1;
            // Let's check to ensure we're only allowing valid values to be passed to the DB
            if($sort_direction === "1" || $sort_direction === "-1") $sort_direction = (int)$sort_direction;

            $options['sort'] = [$params[QUERY_PARAM_SORT_NAME] => $sort_direction ?? 1];
        }
        
        // Now we handle our pagination.
        $options['limit'] = (int)($params[QUERY_PARAM_LIMIT] ?? $this->index_limit);
        
        if(key_exists(QUERY_PARAM_PAGE_NUM, $params)) $options['skip'] = $options['limit'] * ((int)$params[QUERY_PARAM_PAGE_NUM] - 1);
        $this->queryParameters = $options;
    }
}