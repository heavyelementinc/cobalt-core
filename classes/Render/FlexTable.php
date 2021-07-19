<?php

/** FlexTableClass */

namespace Render;

class FlexTable {

    function __construct($id = "", $classes = "") {
        $this->set_id($id);
        $this->set_classes($classes);
        $this->layout = $this->table_layout();
    }

    private function table_layout(): array {
        return [
            'name' => [
                'label' => 'Name',
                'sort' => [
                    'default' => 1,
                    'action' => '/r'
                ],
                'value' => fn ($discount) => "<a href='$GLOBALS[PATH]add/$discount->_id'>$discount->name</a>"
            ]
        ];
    }

    public function render($data = null, $schema = null) {
        if ($data !== null) $this->set_data($data);
        if ($this->data === null) throw new \Exception("Table has no data.");
        $table = "<flex-table id='$this->id' class='$this->classes'>";
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

    private function get_columns() {
        return $this->get_row(null, "header", "label");
    }

    private function get_row($doc = null, $element = "cell", $index = "value") {
        // Somewhere to store our headline
        $head = "<flex-row>";
        // Loop through our layout meta
        foreach ($this->layout as $column => $meta) {
            // Start building our cell
            $cell = "<flex-$element class='$this->header_name" . "$column'>";
            // Check if the column label is callable
            if (is_callable($meta[$index])) {
                $result = $meta[$index](); // "<flex-cell id=''>$something</flex-cell>"
                // Check if the result starts with <flex-header
                if (preg_match("/^<flex-$element/", $result)) {
                    // If it does, overwrite $cell
                    $cell = $result;
                } else {
                    // Otherwise, concat the result to cell and close tag
                    $cell .= $result;
                }
            } else {
                if ($element === "header") $cell .= $meta[$index];
                else if ($doc && isset($doc->{$meta[$index]})) $cell .= $doc->{$meta[$index]};
                else $cell = $meta[$index];
            }

            // Check to make sure we're properly closing our flex-header element
            if (!preg_match("/<\/flex-$element>$/", $cell)) $cell .= "</flex-$element>";

            // Concat this back into the head
            $head .= $cell;
        }
        return "$head</flex-row>";
    }
}
