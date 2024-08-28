<?php

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Controllers\ClientFSManager;
use Controllers\Crudable;
use Drivers\Database;
use Drivers\FSManager;
use Exceptions\HTTP\BadRequest;
use MongoDB\DeleteResult;
use MongoDB\Model\BSONDocument;

class CrudableFiles extends Crudable {
    use ClientFSManager;
    /** @var FSManager */
    public Database $manager;

    public function get_manager(): Database {
        return new FSManager();
    }

    public function get_schema($data): GenericMap {
        return new GenericMap($data, [
            'chunkSize' => [
                new NumberResult,
            ],
            'filename' => [
                // new StringResult,
                'index' => [
                    'title' => 'Sort by Filename',
                    'searchable' => true,
                ],
                'display' => function ($val, $ref) {
                    if($ref instanceof StringResult) return $val;
                    return $ref->getValue()[0];
                }
            ],
            'length' => [
                new NumberResult,
                'index' => [
                    'title' => 'Sort by Size'
                ]
            ],
            'uploadDate' => [
                new DateResult,
                'index' => [
                    'title' => 'Sort by Date',
                    'order' => -2,
                    'sort' => -1
                ]
            ],
            'md5' => [
                new StringResult,
            ],
            'meta.width' => [
                new NumberResult
            ],
            'meta.height' => [
                new NumberResult,
            ],
            'meta.mimetype' => [
                new StringResult,
                'index' => [
                    'title' => 'Sort by Type',
                    'filterable' => true,
                ],
                'valid' => function () {
                    $man = new FSManager();
                    $types = $man->distinct("meta.mimetype");
                    $valid = [];
                    foreach($types as $type) {
                        $valid[$type] = $type;
                    }
                    return $valid;
                }
            ],
            'meta.accent_color' => [
                new HexColorResult
            ],
            'meta.contrast_color' => [
                new HexColorResult
            ]
        ]);
    }

    public function edit($document): string {
        return "";
    }

    public function destroy(GenericMap|BSONDocument $document): array {
        return ['message' => "Are you sure you want to delete this item?"];
    }

    private $index = "/admin/crudable/fs-index.html";
    function index():string {
        return view($this->index);
    }

    function get_table_row(GenericMap $doc, &$html) {
        $html .= $this->manager->fromData($doc);
    }

    static public function route_details_index(array $options = []):array {
        return [
            'permission' => 'Customizations_modify',
            'anchor' => [
                'name' => "FS Manager",
                'icon' => 'palette-swatch-outline',
                'icon_color' => 'linear-gradient(to bottom, #DA627D, #FF495C 80%)'
            ],
            'navigation' => ['application_settings'],
            'handler' => 'admin/fs-manager.js'
        ];
    }

    function delete_file_by_id($id) {
        /** @var DeleteResult */
        $result = $this->delete($id);
        update("[data-id='$id']", ['remove' => true]);
        return $result;
    }

    function file_picker() {
        $this->index = "/admin/crudable/file-picker.html";
        $this->manager->fromDataView = "/admin/crudable/file-picker-container.html";
        return $this->__index();
    }
}