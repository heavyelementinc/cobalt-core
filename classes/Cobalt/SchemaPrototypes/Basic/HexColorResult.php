<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use MikeAlmond\Color\Color;
use Validation\Exceptions\ValidationIssue;

class HexColorResult extends SchemaResult {
    function field($classes = [], $misc = []) {

    }

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

    public function getColor():Color {
        return Color::fromHex($this->value);
    }

    public function cssRGB($alpha = false):string {
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

    public function cssHSL($alpha = false) {
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

    public function getContrastColor($dark = "#000000", $light = "#FFFFFF") {
        $color = $this->getColor();
        $dark = Color::fromHex($dark);
        $darkContrast = $dark->luminosityContrast($color);
        $light = Color::fromHex($light);
        $lightContrast = $light->luminosityContrast($color);
        if($lightContrast > $darkContrast) return "#" . $light->getHex();
        return "#" . $dark->getHex();
    }
}