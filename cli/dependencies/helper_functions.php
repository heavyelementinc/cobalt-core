<?php

function cli_parse_args() {
    $args = $argv;
    array_shift($args);
    $arguments = [];
    foreach ($args as $i => $arg) {
        $values = explode("=", $arg);
        $arguments[str_replace('--', '__', $values[0])] = $values[1];
    }
    return $arguments;
}

function cli_parse_input($string) {
    $raw = explode(" ", $string);
    $args = [];

    for ($i = 0; $i <= count($raw); $i++) {
        // $GLOBALS['cobalt_cli_commands']

    }
}

function readline_parse($input) {
    $pos = explode(' ', $input);
    $command = array_shift($pos);
    $args = implode(' ', $pos);
    if (!empty($args)) $args = json_decode("[$args]", true);
    else $args = [];
    return ['command' => $command, 'args' => $args];
}

function dbg($var) {
    return false;
    print(json_encode($var, JSON_PRETTY_PRINT) . "\n");
}

function cli_to_bool($input, $defaultToYes = false) {
    $allowed = ['y', 'yes', 'true', 'on', 'enable', 'enabled'];
    if ($defaultToYes) array_push($allowed, "");
    return in_array(trim(strtolower($input)), $allowed);
}

function confirm_message($message, $default = false, $additional = "") {
    $auto_prompt = "y/N";
    $default_to_yes = false;
    if (cli_to_bool($default) || $default === true) {
        $auto_prompt = "Y/n";
        $default_to_yes = true;
    }
    $question = readline("$message ($auto_prompt): ");
    return cli_to_bool($question, $default_to_yes);
}

/**
 * Available types:
 *   * `b` - Bold
 *   * `e` - Error
 *   * `s` - Success
 *   * `w` - Warning
 *   * `i` - Information
 *   * `white` - White
 *   * `grey` - Grey
 *   * `normal` - [default] default color
 * @param mixed $str 
 * @param string $type 
 * @param bool $formatted 
 * @return void 
 */
function say($str, $type = "normal", $formatted = false) {
    $fmt = fmt($str, $type);

    if ($formatted !== false) printf($fmt . " \n", $formatted);
    print($fmt . " \n");
}

function say_quietly($str, $type = "normal", $formatted = false) {
    if(!$GLOBALS['fmt_allowed']) return "";
    say($str, $type, $formatted);
}

/**
 * Available types:
 *   * `b` - Bold
 *   * `e` - Error
 *   * `s` - Success
 *   * `w` - Warning
 *   * `i` - Information
 *   * `white` - White
 *   * `grey` - Grey
 *   * `normal` - [default] default color
 * @param mixed $str 
 * @param string $type 
 * @param bool $formatted 
 * @return string 
 */
function fmt($str, $type = "normal", $back = "normal") {
    if(!$GLOBALS['fmt_allowed']) return $str;
    $fmt = "";
    $arr = [
        'b' => '1m'
    ];


    switch ($type) {
        case "b":
            $fmt = "1m";
            break;
        case 'e': //error
            $fmt = "31m";
            break;
        case 's': //success
            $fmt = "32m";
            break;
        case 'w': //warning
            $fmt = "33m";
            break;
        case 'i': //info
            $fmt = "36m";
            break;
        case 'white':
            $fmt = "1;37m";
            break;
        case 'grey':
            $fmt = "37m";
            break;
        case "bblack":
            $fmt = "1;30m\033[107m";
            break;
        case "normal":
        default:
            $fmt = "39m";
    }
    switch ($back) {
        case "red":
            $bg = "\033[41m";
            break;
        case "green":
            $bg = "\033[42m";
            break;
        case "blue":
            $bg = "\033[44m";
            break;
        case "normal":
        default:
            $bg = "";
    }
    return "\033[$fmt$bg$str\033[0m";
}

function log_item($message, $lvl = 1, $type = "grey", $back = "normal") {
    if ($lvl > $GLOBALS['cli_verbosity']) return;
    $date = date('Y-m-d');
    $logpath = __APP_ROOT__ . "/ignored/logs/cobalt-$date.log";
    $resource = fopen($logpath, "a");
    fwrite($resource, "[".date(DATE_RFC2822)."] {$message}\n");
    fclose($resource);
    if(!function_exists("say")) return;
    $m = fmt("[LOG $lvl]", 'i');
    $m .= " " . fmt($message, $type, $back);
    print($m . "\n");
}

function get_image_function($image_data) {
    $valid_types = [
        IMG_GIF => ['imagecreatefromgif', 'imagegif'],
        IMG_JPG => ['imagecreatefromjpeg', 'imagejpeg'],
        3       => ['imagecreatefrompng', 'imagepng'],
        IMG_PNG => ['imagecreatefrompng', 'imagepng'],
        IMG_WBMP => ['imagecreatefromwbmp', 'imagewbmp'],
        IMG_XPM => ['imagecreatefromxpm', 'imagexpm'],
        IMG_WEBP => ['imagecreatefromwebp', 'imagewebp'],
        IMG_BMP => ['imagecreatefrombmp', 'imagebmp'],
    ];
    $valid_types = [
        "image/bmp" => ['imagecreatefrombmp', 'imagebmp'],
        "image/gif" => ['imagecreatefromgif', 'imagegif'],
        "image/jpeg" => ['imagecreatefromjpeg', 'imagejpeg'],
        "image/png" => ['imagecreatefrompng', 'imagepng'],
        "image/wbmp" => ['imagecreatefromwbmp', 'imagewbmp'],
        "image/xpm" => ['imagecreatefromxpm', 'imagexpm'],
        "image/webp" => ['imagecreatefromwebp', 'imagewebp'],
    ];
    if (key_exists($image_data["mime"], $valid_types)) return $valid_types[$image_data["mime"]];
    return null;
}

function image_average_color($path_to_image_file, $null_on_failure = true) { 
    $size = @getimagesize($path_to_image_file);
    if($size === false) {
        if($null_on_failure) return null;    
        else throw new Exception("Not a valid image");
    } 
    $fn = @get_image_function($size);
    if(!$fn) {
        if($null_on_failure) return null;
        else throw new Exception("Invalid image processing function or mimetype");
    }
    $img = $fn($path_to_image_file);
    // $img = @imagecreatefromstring(file_get_contents($imageFile)); 

    if(!$img) {
        if($null_on_failure) return null;
        else throw new Exception("Cannot open image file");
    }

    $scaled = imagescale($img, 1, 1, IMG_BICUBIC);
    $index = imagecolorat($scaled, 0, 0);
    $rgb = imagecolorsforindex($scaled, $index);

    return sprintf('#%02X%02X%02X', $rgb['red'], $rgb['green'], $rgb['blue']);

    // for($x = 0; $x < $size[0]; $x += $granularity) {
    //     for($y = 0; $y < $size[1]; $y += $granularity) {
    //         $thisColor = imagecolorat($img, $x, $y);
    //         $rgb = imagecolorsforindex($img, $thisColor);
    //         $red = round(round(($rgb['red'] / 0x33)) * 0x33);
    //         $green = round(round(($rgb['green'] / 0x33)) * 0x33);
    //         $blue = round(round(($rgb['blue'] / 0x33)) * 0x33);
    //         $thisRGB = sprintf('%02X%02X%02X', $red, $green, $blue);
    //         if(array_key_exists($thisRGB, $colors)) {
    //             $colors[$thisRGB]++;
    //         } else {
    //             $colors[$thisRGB] = 1;
    //         }
    //     }
    // }
    // arsort($colors);
    // return array_slice(array_keys($colors), 0, $number_of_colors);
} 