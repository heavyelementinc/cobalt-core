<?php

namespace Render;

use Exception;

class CLITable {
    function __construct() {
        $this->default_header_item = [
            'title' => null,
            'padding' => 1,
            'function' => fn ($val) => $val,
        ];
    }
    private $header = [];
    private $header_complete = false;
    private $default_header_item = null;
    private $rows = [];
    private $row_widths = [];
    private $columns = 0;

    /**
     * `title`    - Header title
     * `padding`  - The padding alignment STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH
     * `function` - A used to process the cell's data
     * @param mixed $head 
     * @return void 
     */
    function head($head) {
        $this->header = [];
        foreach ($head as $i => $h) {
            $this->header[$i] = array_merge($this->default_header_item, $h);
            if (!$this->header[$i]['title']) $this->header[$i]['title'] = $i;
            $this->width($i, $h['title'], 4);
        }
        $this->header_complete = true;
    }

    function row($row) {
        if (!$this->header_complete) throw new Exception("Header is incomplete! Define a header before adding rows.");
        $index = count($this->rows);
        foreach ($row as $i => $c) {
            $this->rows[$index][$i] = $this->header[$i]['function']($c);
            $this->width($i, $c);
        }

        if (count($this->rows[$index]) > $this->columns) $this->columns = count($this->rows[$index]);
    }

    function insert_rows($data) {
        foreach ($data as $d) {
            $this->row($d);
        }
    }

    function width($key, $c, $pad = 2) {
        $length = strlen($c) + $pad;
        if (!isset($this->row_widths[$key])) return $this->row_widths[$key] = $length;
        if ((strlen($c) + $pad) > $this->row_widths[$key]) $this->row_widths[$key] = $length;
    }

    function clear_rows() {
        $this->rows = [];
        $this->row_widths = [];
        $this->columns = 0;
    }

    function render($data = null) {
        if ($data) $this->insert_rows($data);
        print("| ");
        foreach ($this->header as $i => $cell) {
            $h = fmt(
                // substr(
                str_pad($cell['title'] ?? "", $this->row_widths[$i], " ", STR_PAD_BOTH),
                //     0,
                //     -1
                // ),
                "bblack"
            );
            print($h . " | ");
        }
        print("\n");
        foreach ($this->rows as $row) {
            $this->draw_row($row);
        }
    }

    function draw_row($row) {
        $r = "| ";
        foreach ($this->header as $col => $cell) {
            $r .= str_pad(
                $row[$col],
                $this->row_widths[$col],
                " ",
                $this->header[$col]['padding']
            ) . " | ";
        }
        print("$r\n");
    }
}
