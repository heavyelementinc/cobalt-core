<?php

/** FlexTableClass */

namespace Render;

use Exception;

class FlexTable {
    public $action = null;
    private $headerType = "a";
    private $headerTypes = [];

    function __construct($id = "", $classes = "", $action = null) {
        $this->set_id($id);
        $this->set_classes($classes);
        $this->headerTypes = $this->getHeaderTypes();
        $this->layout = $this->table_layout();
        $this->action = $action;
    }

    function table_layout(): array {
        return [
            'name' => [
                'label' => 'Name',
                'headerElement' => 'a', // Optional
                'sort' => 1,
                'state' => 'default',
                'value' => fn ($value, $discount) => "<a href='$GLOBALS[PATH]add/$discount->_id'>$discount->name</a>"
            ]
        ];
    }

    public function set_header($header) {
        if (!key_exists($header, $this->headerTypes)) throw new Exception("Invalid header type \"$header\"");
        $this->headerType = $header;
    }

    public function render($data = null, $schema = null) {
        if ($data !== null) $this->set_data($data);
        if ($this->data === null) throw new \Exception("Table has no data.");
        $action = ($this->action) ? " action=\"$this->action\"" : "";
        $table = "<flex-table id='$this->id' class='$this->classes'$action>";
        $table .= $this->get_columns();
        foreach ($this->data as $document) {
            if ($schema) $document = new $schema($document);
            $table .= $this->get_row($document);
        }
        return $table;
    }


    public function set_id($id) {
        $this->id = $id;
    }

    public function set_classes($classes) {
        $this->classes = $classes;
    }

    public function set_data($data) {
        $this->data = $data;
    }

    private $header_name = "flex-table--column-name-";

    /** Get the query params for this table, either from existing params
     * or 
     */
    private function getSortParams($field, $column) {
        return [
            'field' => $field,
            'sort' => $column['sort']
        ];
    }

    private function get_columns() {
        $head = "<flex-row class=\"flex-header\">";
        foreach ($this->layout as $column => $meta) {
            // Let's get our attributes and stuff
            $containerElement = $this->headerTypes[$this->headerType]["element"];
            if (isset($meta['headerElement']) && isset($this->headerTypes[$meta['headerElement']])) $containerElement = $meta['headerElement'];
            $attributes = $this->headerTypes[$containerElement]['attrs'];
            if (is_callable($attributes)) $attributes = $attributes($column, $meta);

            // Set
            $data = " data-column-fieldname=\"$column\"";
            $data .= (isset($meta['default'])) ? " data-default-sort=\"$meta[default]\"" : "";
            $data .= (isset($meta['sort']))    ? " data-sort-start=\"$meta[sort]\"" : "";
            $style = (isset($meta['style']))   ? " style=\"$meta[style]\"" : "";
            $cell = "<flex-header class=\"$this->header_name" . "$column\"" . "$style>";
            $cell .= "<$containerElement $attributes $data>$meta[label]</$containerElement>";
            $head .= "$cell</flex-header>";
        }
        return $head . "</flex-row>";
        // return $this->get_row(null, "header", "label");
    }

    private function get_row($doc = null, $element = "cell", $index = "value") {
        // Somewhere to store our headline
        $head = "<flex-row>";
        // Loop through our layout meta
        foreach ($this->layout as $column => $meta) {
            $data = "";

            $style = (isset($meta['style'])) ? " style=\"$meta[style]\"" : "";
            // Start building our cell
            $cell = "<flex-$element class='$this->header_name" . "$column'$data" . "$style>";
            // Check if the column label is callable
            if (is_callable($meta[$index])) {
                $result = $meta[$index]($doc->{$column}, $doc); // "<flex-cell id=''>$something</flex-cell>"
                // Check if the result starts with <flex-header
                if (preg_match("/^<flex-$element/", $result)) {
                    // If it does, overwrite $cell
                    $cell = $result;
                } else {
                    // Otherwise, concat the result to cell and close tag
                    $cell .= $result;
                }
            } else {
                if ($doc && isset($doc->{$meta[$index]})) $cell .= $doc->{$meta[$index]};
                else $cell = $meta[$index];
            }

            // Check to make sure we're properly closing our flex-header element
            if (!preg_match("/<\/flex-$element>$/", $cell)) $cell .= "</flex-$element>";

            // Concat this back into the head
            $head .= $cell;
        }
        return "$head</flex-row>";
    }

    public function get_row_dataset($doc) {
        $dataset = [];
        foreach ($this->layout as $column => $meta) {
            $dataset[$column] = $doc[$column];
            if (is_callable($meta['value'])) {
                $dataset[$column] = $meta['value']($doc, $doc[$column]);
            }
        }
        return $dataset;
    }

    private function getHeaderTypes() {
        return [
            'a' => [
                'element' => "a",
                'attrs' => function ($field, $col) {
                    return $this->currentLinkAttrs($field, $col);
                }
            ],
            'button' => [
                'element' => "button",
                'attrs' => function () {
                    return "";
                }
            ],
            'async-button' => [
                'element' => "async-button",
                'attrs' => function () {
                    return "";
                }
            ],
            'div' => [
                'element' => "div",
                'attrs' => function () {
                    return "";
                }
            ],
            'span' => [
                'element' => "span",
                'attrs' => function () {
                    return "";
                }
            ],
        ];
    }

    private function currentLinkAttrs($field, $col) {
        $current = "";
        if (isset($_GET['field']) && $field === $_GET['field']) {
            $sort = $col['sort']; // Default to the default sort order
            if (in_array($_GET['sort'], ["1", "-1"])) $sort = $_GET['sort']; // Check if sort value is correct
            $current = " flex-table-current=\"$sort\""; // Set item as current with sort order
            $col['sort'] = $sort * -1; // We want to invert the default sort value if we're on the field
        }
        $href = "?" . http_build_query($this->getSortParams($field, $col)); // Set build query
        return "href=\"$href\"$current";
    }

    private function getCurrentOrDefaultParams() {
        $data = [
            'field' => $_POST['field'] ?? null,
            'sort'  => $_POST['sort'] ?? null,
        ];

        $getDefault = false;
        foreach ($data as $f => $d) {
            if ($d === null) {
                $getDefault = true;
                break;
            }

            // Sanitize user input
            $data[$f] = urlencode(filter_var($d, FILTER_SANITIZE_SPECIAL_CHARS));
        }

        if (!$getDefault) return $data;

        foreach ($this->layout as $column => $data) {
            if ($data['state'] === "default") break;
        }

        return [
            'field' => $column,
            'sort' => $data['sort'],
        ];
    }
}
