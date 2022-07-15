<?php

/**
 * "mode": "dark",
 * "background": "#2B2D42",
 * "font-color": "#FFF",
 * "accent-color": "#EF0D1A",
 * "acknowledge-color": "#78E0DC"
 * 
 * DERIVED COLORS:
 * font-color-bold
 * content-border-enabled
 * content-border-disabled
 * content-border-focused
 * content-background-enabled
 * content-background-disabled
 * content-background-focused
 * 
 */
namespace Cobalt\Style;

class Color {
    use ColorTraits, HSV;
    private $hex = "";
    private $rgb = [0,0,0];
    private $hsv = [0,0,0];
    private $mode = "light";

    function __construct(string $color,$mode = "light") {
        $this->hex = $color;
        $this->rgb = $this->color_split($this->hex);
        $this->hsv = $this->RGBtoHSV($this->rgb[0],$this->rgb[1],$this->rgb[2]);
        $this->set_mode($mode);
    }

    public function set_mode($mode) {
        $this->mode = $mode;
    }

    public function get_color_hex() {
        return $this->hex;
    }
    
    public function get_best_contrast($black = "000", $white = "FFF") {
        $black_contrast = $this->luminance_difference($this->hex,$black);
        $white_contrast = $this->luminance_difference($this->hex,$white);
        return ($black_contrast > $white_contrast) ? $black : $white;
    }

    public function adjust(float $hue, float $sat, float $lum) {
        $new_value = [];
        $adjust = [$hue,$sat,$lum];
        $clamp = [360,100,100];
        foreach($this->hsv as $i => $val) {
            $percentage = $val * $adjust[$i];
            $new_value[$i] = ($this->mode == "light") ? abs($val - $percentage) : $val + $percentage;
            $new_value[$i] = $percentage + $adjust[$i];
            // if($new_value[$i] > $clamp[$i]) {
            //     $new_value[$i] = $clamp[$i];
            // }
            // else if ($new_value[$i] < 0) $new_value[$i] = 0;
        }
        return $this->color_hex($this->HSVtoRGB($new_value[0],$new_value[1],$new_value[2]));
    }

    public function derive_border_color(){
        return $this->adjust(1,1,1);
    }

    public function derive_disabled_color() {

    }

    
}