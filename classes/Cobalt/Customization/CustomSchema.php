<?php

namespace Cobalt\Customization;

use Validation\Exceptions\ValidationIssue;
use Validation\Normalize;

class CustomSchema extends Normalize {
    var $valid_meta = [
        'text' => [
            'name' => 'Text',
            'view' => '/customizations/editor/text.html'
        ],
        'image' => [
            'name' => 'Image',
            'view' => '/customizations/editor/image.html'
        ],
    ];

    var $new_view = "/customizations/editor/new.html";

    public function __get_schema(): array {
        $valid_types = [];
        foreach($this->valid_meta as $key => $val) {
            $valid_types[$key] = $val['name'];
        }
        return [
            'type' => [
                // Determines the editor's view
                'valid' => $valid_types,
            ],
            'group' => [
                // Determines where this value falls in the editor
            ],
            'name' => [
                // The name of this value
            ],
            'unique_name' => [
                // Determines how this customization is added into a template
                'set' => function ($val) {
                    $transform = str_replace("-","_",$this->url_fragment_sanitize($val));
                    $man = new CustomizationManager();
                    $count = $man->count(['unique_name' => $transform]);
                    if($count !== 0) throw new ValidationIssue("The name provided is not unique.");
                    return $transform;
                }
            ],
            'description' => [
                // Describe this customization and where it's used
            ],
            'value' => [
                // The value that is inserted into a template
                'display' => function ($val) {
                    return $val[count($val ?? []) - 1] ?? "";
                }
            ],
            'meta' => [
                // Other metadata for this value
            ]
        ];
    }

    function getTemplate($edit = false){
        if(!key_exists('type', $this->__dataset)) return $this->new_view;
        if($edit === true) return $this->new_view;
        return $this->valid_meta[$this->__dataset['type']]['view'];
    }

}
