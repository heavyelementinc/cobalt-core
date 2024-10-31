<?php

use Validation\Exceptions\ValidationIssue;

/** Convert seconds to pretty string */
function prettify_seconds(?int $seconds) {
    if(!$seconds) return "";
    $date = new DateTime("00:00:00");
    $date->modify("+ $seconds seconds");
    return $date->format("g\h i\m");// . "h " . $date->format("i") . "m";
}

/** Convert cents to dollars with decimal fomatting (not prepended by a "$" dollar sign)
 * @param int $cents 
 * @return string the dollar value as a string
 * */
function cents_to_dollars($cents) {
    $dollars = round($cents / 100, 2);
    return number_format($dollars, 2);
}



/**
 * This function returns the maximum files size that can be uploaded 
 * in PHP
 * @return int File size in bytes
 **/
function getMaximumFileUploadSize() {
    return min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
}

/**
 * This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
 * 
 * @param string $sSize
 * @return integer The value in bytes
 */
function convertPHPSizeToBytes($sSize) {
    //
    $sSuffix = strtoupper(substr($sSize, -1));
    if (!in_array($sSuffix, array('P', 'T', 'G', 'M', 'K'))) {
        return (int)$sSize;
    }
    $iValue = substr($sSize, 0, -1);
    switch ($sSuffix) {
        case 'P':
            $iValue *= 1024;
            // Fallthrough intended
        case 'T':
            $iValue *= 1024;
            // Fallthrough intended
        case 'G':
            $iValue *= 1024;
            // Fallthrough intended
        case 'M':
            $iValue *= 1024;
            // Fallthrough intended
        case 'K':
            $iValue *= 1024;
            break;
    }
    return (int)$iValue;
}


function normalize_color($val, $default = null, $normalize = null) {
    if(!$val) $val = "#000000";
    $matches = [];
    $result = preg_match("/^var\((.*)\)$/", $val, $matches);
    if($result) {
        $name = str_replace("--project-","",$matches[1]);
        $val = app("vars-web.$name");
    }

    if (!$val && $default !== null) return $default;
    if (strlen($val) > 8) throw new ValidationIssue("Not a hex color.");
    $pattern = "/^#?[0-9A-Fa-f]{3,6}$/";
    if (!preg_match($pattern, $val)) throw new ValidationIssue("Not a hex color.");
    if($val[0] !== "#" && $normalize) $val = "#$val";
    $length = strlen($val);
    if ($length <= 4) {
        $one = 1;
        $two = 2;
        $three = 3;
        if($val[0] !== "#") {
            $one = 0;
            $two = 1;
            $three = 2;
        }
        $val = "#$val[$one]$val[$one]$val[$two]$val[$two]$val[$three]$val[$three]";
    }
    return preg_replace("/#{2,}/","#",strtoupper($val));
}


/**
 * Clamps a value between the $min and $max value;
 * @param int|float $int 
 * @param int|float $min 
 * @param int|float $max 
 * @return int|float 
 */
function clamp(int|float $current, int|float $min, int|float $max):int|float {
    return max($min, min($max, $current));
}


const FACTOR_MAP = [
    [
        'factor' => 1000,
        'name' => 'thousand',
        'precision' => 1,
        'suffix' => 'k'
    ], [
        'factor' => 1000000,
        'precision' => 1,
        'name' => 'million',
        'suffix' => 'm'
    ], [
        'factor' => 1000000000,
        'precision' => 1,
        'name' => 'billion',
        'suffix' => 'b',
    ], [
        'factor' => 1000000000000,
        'precision' => 1,
        'name' => 'trillion',
        'suffix' => 't',
    ]
];

function pretty_rounding($number, $type = 'suffix', $join = ""):string{
    if($number === 0) return "zero";
    if(is_null($number)) return "zero";
    
    $map = FACTOR_MAP;
    
    if($number < $map[0]['factor']) return $number;

    foreach($map as $data) {
        if($number < $data['factor']) continue;
        if(!key_exists($type, $data)) $type = "suffix";
        $result = round($number / $data['factor'], $data['precision'], PHP_ROUND_HALF_UP) . $join . $data[$type];
    }

    return $result;
}

function pretty_numeral($number):string {
    return pretty_rounding($number, 'name', " ");
}

function countSetBits($n) {
    $count = 0;
    while ($n)
    {
        $count += $n & 1;
        $n >>= 1;
    }
    return $count;
}