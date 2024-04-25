<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use MikeAlmond\Color\Color;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;

class HexColorResult extends SchemaResult {
    use Fieldable;

    protected $type = "color";

    function filter($val) {
        if($val[0] === "#") $val = substr($val, 1);
        $strlen = strlen($val);
        switch($strlen) {
            case 3:
            case 6:
                if(!ctype_xdigit($val)) throw new ValidationIssue("This value contains invalid characters");
                return "#" . $val;
                break;
            default:
                throw new ValidationIssue("This is not a valid color value");
        }
    }

    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    #[Prototype]
    protected function field($classes = [], $misc = []) {
        return $this->input($classes, $misc);
    }

    #[Prototype]
    protected function lighten($percentage) {
        $color = Color::fromHex($this->value); 
        return $color->lighten($percentage);
    }

    #[Prototype]
    protected function darken($percentage) {
        $color = Color::fromHex($this->value); 
        return $color->lighten(-1 * $percentage);
    }

    #[Prototype]
    protected function mix(Color $color, float $percentage = 50) {
        $thisColor = Color::fromHex($this->value); 
        return $color->mix($thisColor, $percentage);
    }

    #[Prototype]
    protected function adjustHue() {

    }

    #[Prototype]
    protected function getColor():Color {
        return Color::fromHex($this->value);
    }

    #[Prototype]
    protected function cssRGB($alpha = false):string {
        $color = $this->getColor();
        $c = $color->getRgb();
        $a = "";
        $v = "";
        if($alpha) {
            $a = "a";
            $v = ", $alpha";
        }
        return "rgb$a($c[0], $c[1], $c[2]$v)";
    }

    #[Prototype]
    protected function cssHSL($alpha = false) {
        $color = $this->getColor();
        $c = $color->getHsl();
        $a = "";
        $v = "";
        if($alpha) {
            $a = "a";
            $v = ", $alpha";
        }
        return "hsl$a($c[0], $c[1], $c[2]$v)";
    }

    #[Prototype]
    protected function getContrastColor($dark = "#000000", $light = "#FFFFFF") {
        $color = $this->getColor();
        $dark = Color::fromHex($dark);
        $darkContrast = $dark->luminosityContrast($color);
        $light = Color::fromHex($light);
        $lightContrast = $light->luminosityContrast($color);
        if($lightContrast > $darkContrast) return "#" . $light->getHex();
        return "#" . $dark->getHex();
    }
}