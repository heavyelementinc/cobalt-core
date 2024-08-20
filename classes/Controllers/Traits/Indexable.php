<?php

namespace Controllers\Traits;

use Cobalt\SchemaPrototypes\Basic\Anchor;
use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\SchemaResult;
use Exception;
use Exceptions\HTTP\Error;
use MongoDB\Database;

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
trait Indexable {
    protected GenericMap $schema;
    protected array $indexableSchema;
    protected array $sortedTable;
    protected array $queryParameters = [];

    public function init(GenericMap $schema, array $params) {
        $this->schema = $schema;
        $this->indexableSchema = $schema->readSchema();
        $table = [];

        $order = 0;
        foreach($this->indexableSchema as $field => $directives) {
            $order += 1;
            $candidate = $this->get_field($field, $directives);
            if(!$candidate) continue;
            $table[$field] = $candidate;
        }

        // Sanity check to ensure we're not moving forward with an empty table
        if(empty($table)) throw new Error("Schema is missing indexable directives");

        // Sort our table
        usort($table, function ($a, $b) {
            if($a['order'] == $b['order']) return 0;
            ($a['order'] < $b['order']) ? -1 : 1;
        });

        $this->sortedTable = $table;
        $this->param_sanity_check($params);
    }

    public function get_table_header() {
        $safe_get_params = [];
        // Let's make our get paramters safe to embed in the page
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

    /** Override this in your controller to set default query options for your index */
    public function index_options():array {
        return [];
    }

    public function get_table_body() {
        $result = $this->manager->find($this->index_query(), array_merge($this->index_options(), $this->queryParameters));
        $html = "";
        foreach($result as $doc) {
            $this->get_table_row($doc, $html);
        }
        return $html;
    }

    /**
     * 
     * @param GenericMap $doc 
     * @param mixed &$html 
     * @return void 
     * @throws Exception 
     */
    private function get_table_row(GenericMap $doc, &$html) {
        $html .= "<flex-row>";
        if($this->schema->__get_index_checkbox_state()) {
            $html .= "<flex-cell class=\"doc_id_mark\"><input type=\"checkbox\" name=\"_id\" value=\"$doc->_id\"></flex-cell>";
        }
        $route = route("$this->name@__edit", [$doc->_id]);
        // Get each cell's contents
        foreach($this->sortedTable as $cell) {
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
                if(!is_string($view) && is_callable($view)) $view = $schema[$cell['name']]['index']['view']($doc[$cell['name']], $doc);
                
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
                $open = "<a href=\"$route\">";
                $close = "</a>";
            }
            $html .= $open . $view . $close . "</flex-cell>";
        }
        $html .= "</flex-row>";
    }

    
    public function get_hypermedia():array {
        $count = $this->manager->count($this->index_query(), $this->index_options());

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
        $next->setHref("?" . http_build_query(array_merge($_GET, [QUERY_PARAM_PAGE_NUM => $nx_page])));
        $next->setText("<i name=\"chevron-right\"></i>");
        
        $prev = new Anchor();
        if($prev_page < 1) {
            $prev->setDisabled(true);
            $prev_page = 1;
        }
        $prev->setHref("?" . http_build_query(array_merge($_GET, [QUERY_PARAM_PAGE_NUM => $prev_page])));
        $prev->setText("<i name=\"chevron-left\"></i>");

        $multidelete_button = "";
        if($this->schema->__get_index_checkbox_state()) {
            $multidelete_button = "<async-button type=\"multidelete\" method=\"DELETE\" action=\"".route(self::className()."@__multidestroy")."\"><i name=\"delete\"></i></async-button>";
        }

        return ['previous' => $prev, 'next' => $next, 'page' => $current_page, 'search' => '', 'multidelete_button' => $multidelete_button];
    }

    /** All you need in order to have a field be included in the index is to include
     * the 'index' key. Things will be inherited 
     */
    final protected function get_field(string $field, array $directives):?array {
        if(!key_exists('index', $directives)) return null;
        $array = [
            'name' => $field,
            'title' => $this->get_title($field, $directives),
            'order' => $this->get_order($field, $directives),
            'sort' => $this->get_sort($field, $directives),
            'view' => $this->get_view($field, $directives),
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
        $options['limit'] = $params[QUERY_PARAM_LIMIT] ?? $this->index_limit;
        
        if(key_exists(QUERY_PARAM_PAGE_NUM, $params)) $options['skip'] = $options['limit'] * ((int)$params[QUERY_PARAM_PAGE_NUM] - 1);
        $this->queryParameters = $options;
    }
}