<?php

namespace Cobalt;

use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use MikeAlmond\Color\Color;

class ThemeManager {
    private Color $primary;
    private Color $background;
    private Color $mixed;
    private float $mixPercent;

    function __construct(string $primary, string $background, float $mix) {
        $this->primary = Color::fromHex($primary);
        $this->background = Color::fromHex($background);
        $this->mixPercent = $mix;
        $this->mixed = $this->primary->mix($this->background, $this->mixPercent);
    }

    function getPrimaryColor() {
        $colors  = $this->getColors("primary-000", $this->primary, "getHex", []);
        $colors .= $this->getColors("primary-100", $this->primary, "lighten", [30]);
        $colors .= $this->getColors("primary-200", $this->primary, "lighten", [50]);
        $colors .= $this->getColors("primary-300", $this->primary, "lighten", [60]);
        $colors .= $this->getColors("primary-400", $this->primary, "lighten", [70]);
        $colors .= $this->getColors("primary-500", $this->primary, "lighten", [80]);
        return $colors;
    }

    function getBackgroundColor() {

        $function = (__APP_SETTINGS__['default_color_scheme']) ? "lighten" : "darken";
        
        $colors  = $this->getColors("background-000", $this->background, "getHex", []);
        $colors .= $this->getColors("background-100", $this->background, $function, [20]);
        $colors .= $this->getColors("background-200", $this->background, $function, [40]);
        $colors .= $this->getColors("background-300", $this->background, $function, [60]);
        $colors .= $this->getColors("background-400", $this->background, $function, [80]);
        $colors .= $this->getColors("background-500", $this->background, $function, [90]);
        return $colors;
    }

    function getMixedColor() {
        $colors  = $this->getColors("mixed-000", $this->mixed, "getHex", []);
        $colors .= $this->getColors("mixed-100", $this->mixed, "lighten", [30]);
        $colors .= $this->getColors("mixed-200", $this->mixed, "lighten", [50]);
        $colors .= $this->getColors("mixed-300", $this->mixed, "lighten", [60]);
        $colors .= $this->getColors("mixed-400", $this->mixed, "lighten", [70]);
        $colors .= $this->getColors("mixed-500", $this->mixed, "lighten", [80]);
        return $colors;
    }

    function getColors($name, $color, $method, array $args) {
        $c = $color->{$method}(...$args);
        $colorInstance = new HexColorResult();
        $colorInstance->setValue($c);
        $val = "--$name: #$colorInstance;\n";
        $val .= "--$name-c: #" . $colorInstance->getContrastColor() .";\n";
        return $val;
    }
}