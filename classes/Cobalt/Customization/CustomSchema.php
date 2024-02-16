<?php

namespace Cobalt\Customization;

use DOMXPath;
use Exception;
use Validation\Exceptions\ValidationIssue;
use Validation\Normalize;

class CustomSchema extends Normalize {
    var $valid_meta = [
        'text' => [
            'name' => 'Text',
            'view' => '/customizations/editor/text.html'
        ],
        'markdown' => [
            'name' => 'Markdown',
            'view' => '/customizations/editor/markdown.html'
        ],
        'image' => [
            'name' => 'Image',
            'view' => '/customizations/editor/image.html'
        ],
        'href' => [
            'name' => 'Embedded URL',
            'view' => '/customizations/editor/embed.html',
        ],
        'video' => [
            'name' => 'Video',
            'view' => '/customizations/editor/video.html',
        ],
        'audio' => [
            'name' => 'Audio',
            'view' => '/customizations/editor/audio.html',
        ],
        'color' => [
            'name' => 'Color',
            'view' => '/customizations/editor/color.html',
        ],
        'series' => [
            'name' => 'Series',
            'view' => '/customizations/editor/series.html',
        ]
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
                    if($this->allowedNameCollision === $transform) return $transform;
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
                'get' => function ($val) {
                    if($this->type === "text" && gettype($val) === "array") {
                        $ct = count($val);
                        return $val[$ct - 1];
                    }
                    return $val;
                },
                'set' => fn ($val) => $this->process_value($val),
                'display' => function ($val) {
                    if(gettype($val) === "string") return $val;
                    return $val[count($val ?? []) - 1] ?? "";
                }
            ],
            'meta' => [
                // Other metadata for this value
            ],
            'meta.series_edit_template' => [

            ],
            'meta.series_display_template' => [

            ],
            'meta.display_width' => [
                'get' => fn($val) => $val ?? $this->__dataset['meta']['width'] ?? $this->__dataset['meta']['meta']['width'],
                'set' => fn($val) => (is_numeric($val)) ? (int)$val : throw new ValidationIssue("Must be numerical value"),
            ],
            'meta.display_height' => [
                'get' => fn($val) => $val ?? $this->__dataset['meta']['height'] ?? $this->__dataset['meta']['meta']['height'],
                'set' => fn($val) => (is_numeric($val)) ? (int)$val : throw new ValidationIssue("Must be numerical value"),
            ],
            "meta.accent_color" => [
                'get' => fn($val) => $val ?? $this->__dataset['meta']['accent_color'] ?? $this->__dataset['meta']['meta']['accent_color'],
                'set' => fn($val) => ($this->hex_color($val)) ? (int)$val : throw new ValidationIssue("Must be a hex color"),
            ],
            "meta.contrast_color" => [
                'get' => fn($val) => $val ?? $this->__dataset['meta']['contrast_color'] ?? $this->__dataset['meta']['meta']['contrast_color'],
                'set' => fn($val) => ($this->hex_color($val)) ? (int)$val : throw new ValidationIssue("Must be a hex color"),
            ],
            'meta.controls' => [
                'get' => fn($val) => $this->getDefault($val,'meta.controls'),
                'set' => fn ($val) => $this->boolean_helper($val),
                'display' => fn($val) =>$this->set_attribute($val,'controls'),
                'default' => true
            ],
            'meta.loop' => [
                'get' => fn($val) => $this->getDefault($val,'meta.loop'),
                'set' => fn ($val) => $this->boolean_helper($val),
                'display' => fn($val) =>$this->set_attribute($val,'loop'),
            ],
            'meta.autoplay' => [
                'get' => fn($val) => $this->getDefault($val,'meta.autoplay'),
                'set' => fn ($val) => $this->boolean_helper($val),
                'display' => fn($val) =>$this->set_attribute($val,'autoplay'),
            ],
            'meta.mute' => [
                'get' => fn($val) => $this->getDefault($val,'meta.mute'),
                'set' => fn ($val) => $this->boolean_helper($val),
                'display' => fn($val) =>$this->set_attribute($val,'muted'),
            ],
            'meta.mimetype' => [
                'valid' => [
                    'href/www.youtube.com'    => 'YouTube',
                    'href/player.vimeo.com'   => 'Vimeo',
                ]
            ],
            'meta.title' => [
                'get' => fn($val) => $this->getDefault($val,'meta.title'),
            ],
            'meta.allow' => [
                'get' => fn($val) => $this->getDefault($val,'meta.allow'),
            ],
            'meta.allowfullscreen' => [
                'get' => fn($val) => $this->getDefault($val,'meta.allowfullscreen'),
            ],
        ];
    }

    function getDefault($val, $name) {
        return $val ?? $this->__schema[$name]['default'] ?? null;
    }

    function getTemplate($edit = false){
        if(!key_exists('type', $this->__dataset)) return $this->new_view;
        if($edit === true) return $this->new_view;
        return $this->valid_meta[$this->__dataset['type']]['view'];
    }

    public function __toString() {
        return $this->value;
    }

    public function set_attribute($val, $attr) {
        if($val) return " $attr='$attr'";
        return "";
    }

    private $allowedNameCollision = null;

    public function allowNameCollision($name) {
        $this->allowedNameCollision = $name;
    }

    // <iframe width="560" height="315" src="https://www.youtube.com/embed/_40ji_0vYP4" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>

    public function process_value($val) {
        $iframe = "<iframe";
        if(substr($val,0,strlen($iframe)) === $iframe) return $this->process_iframe($val);
        return $val;
    }

    private function process_iframe($val) {
        $dom = new \DomDocument();
        $dom->loadHTML($val);
        $path = new DOMXPath($dom);
        $iframe = $path->query("//iframe");
        if($iframe->length <= 0) throw new ValidationIssue("The embed HTML (iframe) appears to be invalid");
        $nodes = iterator_to_array($iframe);
        $attributes = [
            'width',
            'height',
            'src',
            'title',
            'allow',
            'allowfullscreen',
        ];
        $meta = [];
        foreach($attributes as $attr) {
            $meta[$attr] = $nodes[0]->getAttribute($attr);
        }
        $rt = $meta['src'];
        unset($meta['src']);
        $meta['type'] = "href/".parse_url($rt,PHP_URL_HOST);
        foreach($meta as $attr => $value) {
            $this->__dataset["meta.$attr"] = $value;
        }
        return $rt;
    }
}
