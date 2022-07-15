<?php

namespace Cobalt\Style;

use Exception;

trait ColorTraits {
    /**
     * Hex color validation. If $default is anything other than null, then $default
     * will be set as the value and no error will be thrown.
     * 
     * @param string $val The hex color starting with a #
     * @param null|string $val The default hex value to use
     * @return string Uppercased 6 digit hex color starting with #
     */
    final protected function hex_color($val, $default = null) {
        if (!$val && $default !== null) return $default;
        if (strlen($val) > 8) throw new Exception("Not a hex color.");
        $pattern = "/^#[0-9A-Fa-f]{3,6}$/";
        if (!preg_match($pattern, $val)) throw new Exception("Not a hex color.");
        if (strlen($val) === 4) $val = "#$val[1]$val[1]$val[2]$val[2]$val[3]$val[3]";
        return strtoupper($val);
    }

    /**
     * Returns the input $value if, and only if, the comparison of the two hex
     * value's luminosity meets the $threshold. Default is 5.
     * 
     * For best accessibility, 5 is optimum.
     * 
     * @param string $val the hex color to evaluate
     * @param string $comparisonHex the hex color used as a baseline for comparison
     * @param int|float $threshold minum luminosity difference. Min 0, max 5 (default);
     * @return int|float 
     * @throws ValidationIssue 
     */
    final protected function contrast_color($value, $comparisonHex, $threshold = 5) {
        // Normalize our inputs and error out if invalid hex
        $val = $this->hex_color($value);
        $comp = $this->hex_color($comparisonHex);

        // Color split
        [$R1, $G1, $B1] = $this->color_split($val);
        [$R2, $G2, $B2] = $this->color_split($comp);

        $L1 = 0.2126 * pow($R1 / 255, 2.2) +
            0.7152 * pow($G1 / 255, 2.2) +
            0.0722 * pow($B1 / 255, 2.2);

        $L2 = 0.2126 * pow($R2 / 255, 2.2) +
            0.7152 * pow($G2 / 255, 2.2) +
            0.0722 * pow($B2 / 255, 2.2);

        if ($L1 > $L2) {
            $result = ($L1 + 0.05) / ($L2 + 0.05);
            $scale = "brighter";
        } else {
            $result = ($L2 + 0.05) / ($L1 + 0.05);
            $scale = "darker";
        }
        if ($result < $threshold) {
            throw new Exception("This color must be $scale for readability purposes.");
        }
        return $val;
    }


    final protected function color_split($val) {
        if ($val[0] === "#") $val = substr($val, 1);
        return [
            hexdec($val[0] . $val[1]),
            hexdec($val[2] . $val[3]),
            hexdec($val[4] . $val[5]),
        ];
    }

    final protected function color_hex(array $val) {
        return "#" . dechex($val[0]) . dechex($val[1]) . dechex($val[2]);
    }

    final protected function color_diff($color1,$color2){
        [$R1,$G1,$B1] = $this->color_split($color1);
        [$R2,$G2,$B2] = $this->color_split($color2);

        return max($R1,$R2) - min($R1,$R2) +
               max($G1,$G2) - min($G1,$G2) +
               max($B1,$B2) - min($B1,$B2);
    }

    final protected function brightness_difference($color1,$color2){
        [$R1,$G1,$B1] = $this->color_split($color1);
        [$R2,$G2,$B2] = $this->color_split($color2);
        $BR1 = (299 * $R1 + 587 * $G1 + 114 * $B1) / 1000;
        $BR2 = (299 * $R2 + 587 * $G2 + 114 * $B2) / 1000;
     
        return abs($BR1-$BR2);
    }

    final protected function luminance_difference($color1,$color2){
        [$R1,$G1,$B1] = $this->color_split($color1);
        [$R2,$G2,$B2] = $this->color_split($color2);
        $L1 = 0.2126 * pow($R1/255, 2.2) +
              0.7152 * pow($G1/255, 2.2) +
              0.0722 * pow($B1/255, 2.2);
     
        $L2 = 0.2126 * pow($R2/255, 2.2) +
              0.7152 * pow($G2/255, 2.2) +
              0.0722 * pow($B2/255, 2.2);
     
        if($L1 > $L2){
            return ($L1+0.05) / ($L2+0.05);
        }else{
            return ($L2+0.05) / ($L1+0.05);
        }
    }

    function pythagorian_difference($color1,$color2){
        [$R1,$G1,$B1] = $this->color_split($color1);
        [$R2,$G2,$B2] = $this->color_split($color2);
        $RD = $R1 - $R2;
        $GD = $G1 - $G2;
        $BD = $B1 - $B2;
     
        return  sqrt( $RD * $RD + $GD * $GD + $BD * $BD ) ;
    }
}