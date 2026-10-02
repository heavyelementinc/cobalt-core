<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Types\DocumentType;
use Cobalt\DataModel\Types\Generic;

/**
 * @mixin DocumentType
 */
trait ControllerIndex {
    /**
     * @return array{filter:array,options:array}
     */
    abstract function indexDetails():array;

    private $querySortParams = [];
    function registerQuerySortParams(string $field, int $sort) {
        if(!$sort) return;
        $this->querySortParams[$field] = $sort;
    }

    function getHyperlink(string $field = "", int $invert = 0, bool $exclusive = false):string {
        $sortParams = $this->querySortParams;
        
        if($field && $invert) $sortParams[$field] = $invert * -1;
        if($exclusive === true) {
           $sortParams = [$field => ($invert) ? $invert * -1 : 1];
        }
        
        return "?".http_build_query(['sort' => $sortParams]);
    }

    /**
     * Dynamically build a list of fields that should be displayed on this index
     * @return string[]
     */
    public function getTableColumns():array {
        $defaultFieldName = $this->getDefaultField()->name;
        $fields = [$defaultFieldName];
        /**
         * @var string $field
         * @var Generic $value
         */
        foreach($this as $field => $value) {
            if(!$value->directives->hasDirective('index')) continue;
            if($field === $defaultFieldName) continue;
            array_push($fields, $field);
            $value->initIndexItem($_GET, $this);
        }
        return $fields;
    }

    public function controllerIndexGetIndex():string{
        $table = $this->getTableColumns();
        $projection = array_combine($table, array_fill(0, count($table), 1));
        $query = $this->indexDetails();
        $filter = $query['filter'];
        $options = $query['options'];
        if(key_exists('projection', $options)) {
            $options['projection'] = [...$projection, ...$options['projection']];
        } else {
            $options['projection'] = $projection;
        }
        $options['projetion']['_id'] = 1;

        $result = $this->find($filter, $options);
        
        $table_body = "";
        /**
         * @var DocumentType $document
         */
        foreach($result as $document) {
            $tr = "";
            foreach($table as $field) {
                $f = $document->{$field};
                if($f instanceof Generic === false) continue;
                $this->renderField($tr, $f, $document);
            }
            if(!$tr) continue;
            $table_body .= <<<HTML
            <tr>
                <td><input type="checkbox" value="$document->_id"></td>
                $tr
            </tr>
            HTML;
        }

        $table_header = $this->getTableHeader($table);

        return <<<HTML
        <table-container>
            <table>
                <tr>
                    <th><input type="checkbox"></th>
                    $table_header
                </tr>
                <tbody>
                    $table_body
                </tbody>
            </table>
        </table-container>
        HTML;
    }

    public function renderField(string &$table, Generic $field, DocumentType $document) {
        $table .= $field->getIndexCell($document->getHref());
    }

    public function getTableHeader(array $table):string {
        $html = "";
        foreach($table as $field) {
            $html .= $this->{$field}->getIndexHeader($this);
        }
        return $html;
    }

    public function getHref():string {
        return "";
    }
}