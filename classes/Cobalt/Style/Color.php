<?php

/**
 * "mode": "dark",
 * "background": "#2B2D42",
 * "font-color": "#FFF",
 * "accent-color": "#EF0D1A",
 * "acknowledge-color": "#78E0DC"
 * 
 * DERIVED COLORS:
 * font-color-normal
 * font-color-bold
 * body-background
 * content-border
 * content-background-even
 * content-background-odd
 * element-border-enabled
 * element-border-disabled
 * element-border-focused
 * element-border-accent-normal
 * element-border-accent-error
 * element-background-enabled
 * element-background-disabled
 * element-background-focused
 * element-background-accent-normal
 * element-background-accent-error
 */
namespace Cobalt\Style;

use MikeAlmond\Color\Color as ColorColor;
use MikeAlmond\Color\PaletteGenerator;

define("BORDER_LUMINENCE", .7);

/**
 * Functions should only ever RETURN a value and NEVER mutate the root $hex color
 * @package Cobalt\Style
 */
class Color {
    // use ColorTraits, HSV;
    private $color = "";
    private $mode = "light";
    private $adjacent = [];


    private $derived = [
        'body-background' => [
            'method' => "body_background",
            'luminence_adjust' => [
                'light' => .99,
                'dark' => .1,
            ],
        ],
        'container-background' => [
            'method' => "body_background",
            'luminence_adjust' => [
                'light' => .94,
                'dark' => .1,
            ],
        ],
        'font-color-normal' => [
            'method' => "font_color_normal",
            'luminence_adjust' => [
                'light' => 0,
                'dark' => 1,
            ],
        ],
        'font-color-bold' => [
            'method' => "font_color_bold",
            'luminence_adjust' => [
                'light' => 1,
                'dark' => 0,
            ],
        ],
        'content-border' => [
            'method' => "content_border",
            'luminence_adjust' => [
                'light' => BORDER_LUMINENCE,
                'dark' => .1,
            ],
        ],
        'content-background-even' => [
            'method' => "content_background_even",
            'luminence_adjust' => [
                'light' => .98,
                'dark' => .1,
            ],
        ],
        'content-background-odd' => [
            'method' => "content_background_odd",
            'luminence_adjust' => [
                'light' => .95,
                'dark' => .1,
            ],
        ],
        'element-border-enabled' => [
            'method' => "element_border_enabled",
            'luminence_adjust' => [
                'light' => BORDER_LUMINENCE,
                'dark' => .1,
            ],
        ],
        'element-border-disabled' => [
            'method' => "element_border_disabled",
            'luminence_adjust' => [
                'light' => BORDER_LUMINENCE + .25,
                'dark' => .1,
            ],
        ],
        'element-border-focused' => [
            'method' => "element_border_focused",
            'luminence_adjust' => [
                'light' => BORDER_LUMINENCE - .2,
                'dark' => .1,
            ],
        ],
        'element-border-accent-normal' => [
            'method' => "element_border_accent_normal",
            'luminence_adjust' => [
                'light' => .9,
                'dark' => .1,
            ],
        ],
        'element-border-accent-error' => [
            'method' => "element_border_accent_error",
            'luminence_adjust' => [
                'light' => .9,
                'dark' => .1,
            ],
        ],
        'element-background-enabled' => [
            'method' => "element_background_enabled",
            'luminence_adjust' => [
                'light' => .9,
                'dark' => .1,
            ],
        ],
        'element-background-disabled' => [
            'method' => "element_background_disabled",
            'luminence_adjust' => [
                'light' => .9,
                'dark' => .1,
            ],
        ],
        'element-background-focused' => [
            'method' => "element_background_focused",
            'luminence_adjust' => [
                'light' => .9,
                'dark' => .1,
            ],
        ],
        'element-background-accent-normal' => [
            'method' => "element_background_accent_normal",
            'luminence_adjust' => [
                'light' => .9,
                'dark' => .1,
            ],
        ],
        'element-background-accent-error' => [
            'method' => "element_background_accent_error",
            'luminence_adjust' => [
                'light' => .9,
                'dark' => .1,
            ],
        ],
    ];

    function __construct(string $color,$mode = "light") {
        $this->color = ColorColor::fromHex($color);
        $palette = new PaletteGenerator($this->color);
        $this->adjacent = $palette->adjacent();
        $this->set_mode($mode);
    }

    public function set_mode($mode) {
        $this->mode = $mode;
    }
    
    public function get_best_contrast($black = "000000", $white = "FFFFFF") {
        $white_contrast = $this->color->luminosityContrast(ColorColor::fromHex($white));
        $black_contrast = $this->color->luminosityContrast(ColorColor::fromHex($black));
        return ($black_contrast > $white_contrast) ? "$black" : "$white";
    }

    function font_color_normal($n) {
        $m = [
            'light' => ['000000', 'FFFFFF'],
            'dark'  => ['010101', 'FEFEFE'],
        ];
        return "#".$this->get_best_contrast($this->derived['container-background'], $m[$this->mode][0], $m[$this->mode][1]);
    }
    function font_color_bold($n) {
        return "#".$this->get_best_contrast();
    }

    function body_background($n) {
        return $this->relativeLightening($this->color, false, true, $n);
    }

    function content_border($n) {
        return $this->relativeLightening(
            $this->adjustSaturation($this->color, .6),
            false, true, $n
        );
    }

    function content_background_even($n) {
        return $this->relativeLightening(
            $this->adjustSaturation($this->color, .4),
            false, true, $n
        );
    }

    function content_background_odd($n) {
        return $this->relativeLightening(
            $this->adjustSaturation($this->color, .6),
            false, true, $n
        );
    }


    function element_border_enabled($n) {
        return $this->relativeLightening(
            $this->color,
            false, true, $n
        );
    }
    
    function element_border_disabled($n) {
        return $this->relativeLightening(
            $this->adjustSaturation($this->color, .4),
            false, true, $n
        );
    }
    
    function element_border_focused($n) {
        return $this->relativeLightening(
            $this->color,
            false, true, $n
        );
    }

    function element_border_accent_error($n) {
        return $this->relativeLightening(
            $this->adjacent[count($this->adjacent) - 1],
            false, true, $n
        );
    }

    function element_background_enabled($n) {
        return $this->relativeLightening(
            $this->color,
            false, true, $n
        );
    }

    function element_background_disabled($n) {
        // str_replace(") ", " / .4)"
        return $this->relativeLightening(
            $this->adjustSaturation($this->color, .4),
            false, true, $n
        );
    }

    function element_background_focused($n) {
        return $this->relativeLightening(
            $this->color,
            false, true, $n
        );
    }

    function element_border_accent_normal($n) {

    }

    function element_background_accent_error($n) {
        return $this->relativeLightening(
            $this->adjacent[count($this->adjacent) - 1],
            false, true, $n
        );
    }

    private function relativeLightening(ColorColor $color, $value, $alpha = true, $name) {
        $arr = $color->getHsl();
        
        $l = $arr['l'];
        if($value === false) $value = $this->derived[$name]['luminence_adjust'][$this->mode];

        $this->derived[$name]['value'] = ColorColor::fromHsl($arr['h'], $arr['s'], $value);
        
        if(is_numeric($alpha)) $alpha = " / $alpha";
        else $alpha = "";
        
        return "rgb(". implode(" ", $this->derived[$name]['value']->getRgb()) . "$alpha) /* $l $value*/";
    }

    public function derive_style_colors_from_accent() {
        $root = "";
        foreach($this->derived as $name => $data) {
            if(!method_exists($this, $data['method'])) continue;
            $d = $this->{$data['method']}($name);
            $root .= "--{$name}: $d;\n";
        }
        return $root;
    }

    public function get_swatch_divs() {
        $root = "";
        foreach($this->derived as $name => $meta) {
            $root .= "<div style='height:200px; width:200px; background: var(--$name)'>$name</div>";
        }
        return $root;
    }

    private function adjustSaturation(ColorColor $color, float $percent) {
        $i = $color->getHsl();
        return ColorColor::fromHsl($i['h'],$i['s'] * $percent,$i['l']);
    }
    
}
