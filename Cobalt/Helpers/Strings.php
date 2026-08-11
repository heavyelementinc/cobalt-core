<?php

namespace Cobalt\Helpers;

use TypeError;

class Strings {
    static function plural(mixed $number, string $suffix = "s", string $singular = "") {
        if ($number == 1) return $singular;
        return $suffix;
    }

    static function fediverse_href_to_user_tag(string $href):?string {
        if(!$href) return null;
        // https://mastodon.social/@heavyelementinc
        $url = parse_url($href);
        $username = substr($url['path'], 1);
        return "$username@$url[host]";
    }

    static function fediverse_handle_to_href(string $handle): ?string {
        return "";
    }

    static function phone_number_format($number, $format = "(ddd) ddd-dddd") {
        if (!$number) return "";
        $num_index = 0;
        $num_max = strlen($number);
        $formatted = "";
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] === "d") {
                if ($num_index >= $num_max) {
                    $formatted .= "n";
                    continue;
                }
                $formatted .= $number[$num_index];
                $num_index++;
            } else {
                $formatted .= $format[$i];
            }
        }
        return $formatted;
    }

    static function phone_number_normalize(string $number) {
        // List of characters we don't want to store in our db
        $junk = ["(", ")", " ", "-", "."];

        // Strip the junk characters out of the string
        $value = str_replace($junk, "", $number);
        return $value;
    }

    static function random_string(int $length, $fromChars = null) {
        $validChars = $fromChars ?? "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $min = 0;
        $max = strlen($validChars) - 1;
        $random = "";
        for ($i = 0; $i <= $length; $i++) {
            $random .= $validChars[random_int($min, $max)];
        }
        return $random;
    }

    static function guidv4(?string $data = null) {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        if(!$data) $data = random_bytes(16);
        else $data = str_pad($data, 16, random_string(16));
        assert(strlen($data) == 16);
    
        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
        $arr = str_split(bin2hex($data), 4);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', $arr);
    }

    static function url_fragment_sanitize(string $value):string {
        $mutant = strtolower($value);
        // Strip apostrophes
        $mutant = preg_replace("/'/", "", $mutant);
        // Remove any character that isn't alphanumerical and replace it with a dash
        $mutant = preg_replace("/([^a-z0-9])/", "-", $mutant);
        // Remove any consecutive dash
        $mutant = preg_replace("/(-){2,}/", "", $mutant);

        if (!$mutant || $mutant === "-") throw new TypeError("\"$value\" is not suitable to transform into a URL fragment");
        return $mutant;
    }
    
    static function from_snake_case(string $name, string $delimiter = " "):string {
        return strtolower(str_replace([".","_"], $delimiter, $name));
    }

    static function from_camel_case(string $name, string $delimiter = " "):string {
        $lower = trim(strtolower(preg_replace(["/([A-Z])/","/[_\.]/"], ["$delimiter$1",$delimiter], $name)));
        if($lower[0] === $delimiter) return substr($lower, 1);
        return $lower;
    }
}
