<?php

namespace Cobalt\Style;

use Exception;
use MikeAlmond\Color\Color;
use MikeAlmond\Color\PaletteGenerator;

define("BACKGROUND_LUM", .95);
define("BACKGROUND_SAT", .7);
define("BORDER_LUMINENCE", .6);
define("BORDER_SATURATION", .6);

class SchemeGenerator {
    private $derived = [
        'body-background' => [
            'method' => "body_background",
            'adjust' => [
                'light' => [
                    'l' => BACKGROUND_LUM + .02,
                ],
                'dark' => .1,
            ],
        ],


        'container-background' => [
            'method' => "body_background",
            'adjust' => [
                'light' => [
                    'l' => 1
                ],
                'dark' => .1,
            ],
        ],
        'font-color-normal' => [
            'method' => "font_color_normal",
            'adjust' => [
                'light' => [
                    'l' => 0
                ],
                'dark' => 1,
            ],
        ],
        'font-color-bold' => [
            'method' => "font_color_bold",
            'adjust' => [
                'light' => [
                    'l' => 1
                ],
                'dark' => 0,
            ],
        ],


        'content-border' => [
            'method' => "content_border",
            'adjust' => [
                'light' => [
                    'l' => BORDER_LUMINENCE,
                    's' => BORDER_SATURATION
                ],
                'dark' => .1,
            ],
        ],


        'content-background-even' => [
            'method' => "content_background_even",
            'adjust' => [
                'light' => [
                    'l' => BACKGROUND_LUM + .02,
                    's' => BACKGROUND_SAT
                ],
                'dark' => .1,
            ],
        ],
        'content-background-odd' => [
            'method' => "content_background_odd",
            'adjust' => [
                'light' => [
                    'l' => BACKGROUND_LUM,
                    's' => BACKGROUND_SAT
                ],
                'dark' => .1,
            ],
        ],


        'element-border-enabled' => [
            'method' => "element_border_enabled",
            'adjust' => [
                'light' => [
                    'l' => BORDER_LUMINENCE,
                    's' => BORDER_SATURATION,
                ],
                'dark' => .1,
            ],
        ],
        'element-background-enabled' => [
            'method' => "element_background_enabled",
            'adjust' => [
                'light' => [
                    'l' => BACKGROUND_LUM + .01,
                ],
                'dark' => .1,
            ],
        ],
        
        'element-border-hover' => [
            'method' => "element_border_hover",
            'adjust' => [
                'light' => [
                    'l' => BORDER_LUMINENCE + .2,
                    's' => 1
                ],
                'dark' => .1,
            ],
        ],
        'element-background-hover' => [
            'method' => "element_background_hover",
            'adjust' => [
                'light' => [
                    'l' => BACKGROUND_LUM + .03
                ],
                'dark' => .1,
            ],
        ],

        'element-border-focused' => [
            'method' => "element_border_focused",
            'adjust' => [
                'light' => [
                    'l' => BORDER_LUMINENCE - .2
                ],
                'dark' => .1,
            ],
        ],
        'element-background-focused' => [
            'method' => "element_background_focused",
            'adjust' => [
                'light' => [
                    'l' => .9
                ],
                'dark' => .1,
            ],
        ],


        'element-border-accent-error' => [
            'method' => "element_error_accent",
            'adjust' => [
                'light' => [
                    'l' => BORDER_LUMINENCE,
                ],
                'dark' => .1,
            ],
        ],
        'element-background-accent-error' => [
            'method' => "element_error_accent",
            'adjust' => [
                'light' => [
                    'l' => .9,
                    's' => BACKGROUND_SAT + .2
                ],
                'dark' => .1,
            ],
        ],        
        

        'element-border-disabled' => [
            'method' => "element_border_disabled",
            'adjust' => [
                'light' => [
                    'l' => BORDER_LUMINENCE + .2,
                    's' => .3,
                    // 'a' => .1
                ],
                'dark' => .1,
            ],
        ],
        'element-background-disabled' => [
            'method' => "element_background_disabled",
            'adjust' => [
                'light' => [
                    'l' => BACKGROUND_LUM - .02,
                    's' => .5
                ],
                'dark' => .1,
            ],
        ],


    ];
    private $colors = [];
    private $color;
    private $adjacent;
    private $mode;
    private $debug;

    function __construct(string $color, $mode = "light", $debug = false) {
        $this->color = Color::fromHex($color);
        $palette = new PaletteGenerator($this->color);
        $this->adjacent = $palette->adjacent();
        $this->set_mode($mode);
        $this->debug = $debug;
    }

    function set_mode($mode) {
        if(!in_array($mode, ['light', 'dark'])) throw new Exception("Attempt to set mode to invalid state");
        $this->mode = $mode;
    }

    public function derive_lumninence(Color $color, $lum, $sat) {
        $arr = $color->getHsl();
        $color = Color::fromHsl($arr['h'], $arr['s'], $lum);
        if(!$sat === false) $color = $this->derive_saturation($color, $sat);
        return $color;
    }

    private function derive_saturation(Color $color, float $percent) {
        $i = $color->getHsl();
        return Color::fromHsl($i['h'], $i['s'] * $percent, $i['l']);
    }

    public function derive_style_colors_from_accent() {
        $root = "";
        foreach($this->derived as $name => $data) {
            $method = "derive_lumninence";
            if(method_exists($this, $data['method'])) $method = $data['method'];

            // Let's grab our values so they're easier to reference
            $adjust = $data['adjust'][$this->mode];

            // CALL our basic 
            $mutant = $this->{$method}($this->color, $adjust['l'], $adjust['s'] ?? false);

            // Store our value for later reference
            $this->colors[$name] = $mutant;
            $readable = $this->get_best_contrast($mutant);

            // Check it this value has an alpha value
            $alpha = "";
            if(is_numeric($adjust['a'] ?? false)) $alpha = " / ".$adjust['a'];

            // Convert this to a CSS RGB value and store the value as a thing
            $rgb = "rgb(". implode(" ", $mutant->getRgb()) . "$alpha)";// . ($this->debug) ? " /* $l $value*/" : "";
            $root .= "--{$name}: $rgb;\n";
            $root .= "--{$name}--readable: #".$mutant->getMatchingTextColor().";\n";
        }
        return $root;
    }


    public function get_best_contrast(Color $color, $black = "000000", $white = "FFFFFF") {
        $white_contrast = $this->color->luminosityContrast(Color::fromHex($white));
        $black_contrast = $this->color->luminosityContrast(Color::fromHex($black));
        return ($black_contrast > $white_contrast) ? "$black" : "$white";
    }

    function font_color_normal($n) {
        $m = [
            'light' => ['000000', 'FFFFFF'],
            'dark'  => ['010101', 'FEFEFE'],
        ];
        return Color::fromHex($this->get_best_contrast($this->colors['container-background'], $m[$this->mode][0], $m[$this->mode][1]));
    }

    function font_color_bold($n) {
        return Color::fromHex($this->get_best_contrast($this->colors['container-background']));
    }

    private $error_accent_index = null;

    function element_error_accent($color, $lum, $sat) {
        $candidate = $this->error_accent_index;
        if(is_null($candidate)) {
            $highest_red = $this->adjacent[0]->getRgb()['r'];
            $candidate = 0;
            foreach($this->adjacent as $index => $a) {
                if($a->getRgb()['r'] < $highest_red) continue;
                $candidate = $index;
            }
            $this->error_accent_index = $candidate;
        }
        return $this->derive_lumninence($this->adjacent[$candidate], $lum, $sat);
    }

    public function get_swatch_divs() {
        $root = "";
        foreach($this->derived as $name => $meta) {
            $root .= "<div style='height:200px; width:200px; background: var(--$name);color:var(--$name--readable'>$name</div>";
        }
        return $root;
    }
}
