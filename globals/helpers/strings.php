<?php

use Demyanovs\PHPHighlight\Highlighter;
use Drivers\UTCDateTime as DriversUTCDateTime;
use MongoDB\BSON\UTCDateTime;
use Validation\Exceptions\ValidationIssue;

function fediverse_href_to_user_tag(string $href) {
    if(!$href) return;
    // https://mastodon.social/@heavyelementinc
    $url = parse_url($href);
    $username = substr($url['path'], 1);
    return "$username@$url[host]";
}

function phone_number_format($number, $format = "(ddd) ddd-dddd") {
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

function phone_number_normalize($number) {
    // List of characters we don't want to store in our db
    $junk = ["(", ")", " ", "-", "."];

    // Strip the junk characters out of the string
    $value = str_replace($junk, "", $number);
    return $value;
}

/**
 * 
 * @param iterator $results the results of a Mongo query
 * @param string $schema_name the name of the schema class
 * @return array|null every instance of the mongo query as a Cobalt schema
 */
function results_to_schema($results, string $schema_name) {
    if ($results === null) return null;
    $array  = [];
    // if ($schema_name instanceof \Validation\Normalize === false) throw new Exception("$schema_name is not an instance of \Validation\Normalize");
    foreach ($results as $i => $doc) {
        $array[$i] = new $schema_name($doc);
    }
    return $array;
}

function plural($number, string $suffix = "s", string $singular = "") {
    if ($number == 1) return $singular;
    return $suffix;
}

function cookie_consent_check() {
    return isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === "all";
}

function sanitize_path_name($path) {
    return str_replace(["../"], "", $path);
}

function relative_time($time = false, $now = null, $limit = 86400, $format = "M jS g:i A") {
    if($time instanceof UTCDateTime || $time instanceof DriversUTCDateTime) $time = $time->toDateTime();
    if($time instanceof DateTime) $time = $time->getTimestamp();
    if (empty($time) || (!is_string($time) && !is_numeric($time))) $time = time();
    else if (is_string($time)) $time = strtotime($time);

    if(is_null($now)) $now = time();
    $relative = '';

    if ($time === $now) $relative = 'now';
    elseif ($time > $now) $relative = 'in the future';
    else {
        $diff = $now - $time;

        if ($diff >= $limit) $relative = date($format, $time);
        elseif ($diff < 60) {
            $relative = 'less than one minute ago';
        } elseif (($minutes = ceil($diff/60)) < 60) {
            $relative = $minutes.' minute'.(((int)$minutes === 1) ? '' : 's').' ago';
        } else {
            $hours = ceil($diff/3600);
            $relative = 'about '.$hours.' hour'.(((int)$hours === 1) ? '' : 's').' ago';
        }
    }

    return $relative;
}


function obscure_email(string $email, int $threshold = 3, string $character = "•"): string {
    $obscured = "";
    $temp_thresh = $threshold;
    $domain = false;
    for($i = 0; $i <= strlen($email) - 1; $i++) {
        if($email[$i] === "@") {
            $temp_thresh = $threshold;
            $domain = true;
        }
        if($email[$i] === "." && $domain) $temp_thresh = 2;

        if($temp_thresh <= 0) {
            $obscured .= $character;
            continue;
        }

        $obscured .= $email[$i];
        $temp_thresh -= 1;
    }
    return $obscured;
}

function country2flag(?string $countryCode, ?string $countryName = null): string {
    if(!$countryCode) return "";
    $unicode = (string) preg_replace_callback(
        '/./',
        static fn (array $letter) => mb_chr(ord($letter[0]) % 32 + 0x1F1E5),
        $countryCode
    );
    return "<span title='$countryName' draggable='false'>" . $unicode . "</span>";
}


function syntax_highlighter($code, $filename = "", $language = "json", $line_numbers = true, $action_panel = false) {
    if(gettype($code) !== "string") $code = json_encode($code, JSON_PRETTY_PRINT);
    $mutant = "<pre data-file='$filename' data-lang='$language'>$code</pre>";
    $highlighter = new Highlighter($mutant, 'railscasts');
    $highlighter->setShowLineNumbers($line_numbers);
    $highlighter->setShowActionPanel($action_panel);
    return $highlighter->parse();
}


/**
 * Given this structure:
 * [
 *    "key" => [
 *       "value" => [
 *           "nested" => true
 *       ],
 *       "other" => false
 *    ],
 *    ...
 * ]
 * 
 * This function will return:
 * [
 *    "key.value.nested" => true,
 *    "key.other" => false,
 *    ...
 * ]
 * @param mixed $array 
 * @param string $toplevel 
 * @return void 
 */
// function flatten_array_to_js_notation($array, $toplevel = null) {
//     $flattened = [];
//     // if($toplevel) $toplevel = "$toplevel.";
//     foreach($array as $key => $val) {
//         $mutant = [];
//         if(is_object($val) && $val instanceof jsonSerializable) {
//             $val = $val->__jsonSerialize();
//         }
//         if(is_array($val)) {
//             $val = flatten_array_to_js_notation($array, $key);
//             continue;
//         }
//         // $newkey = $toplevel.$key;
//         $flattened[$newkey] = 
//     }
// }

function convertFractionToChar($string) {
    return str_replace(" ", "", str_replace(
        ["1/4",   "1/2",   "3/4",   "1/7",    "1/9",    "1/10",   "1/3",    "2/3",    "1/5",    "2/5",    "3/5",    "4/5",    "1/6",    "5/6",    "1/8",    "3/8",    "5/8",    "7/8"],
        ["&#188;","&#189;","&#190;","&#8528;","&#8529;","&#8530;","&#8531;","&#8532;","&#8533;","&#8534;","&#8535;","&#8536;","&#8537;","&#8538;","&#8539;","&#8540;","&#8541;","&#8542;"],
        $string
    ));
}

function convertCommonTextElements($string) {
    return str_replace(
        ['--'],
        ['—'],
        $string
    );
}


/**
 * from_markdown
 *
 * @param  string $string - The string you wish to parse as markdown
 * @param  bool $untrusted - Whether the markdown is user input
 * @return string - HTML-formatted string
 */
function from_markdown($string, bool $untrusted = true) {
    if(!$string) return "";
    if(gettype($string) !== "string") return $string;
    
    $md = new ParsedownExtra();
    $md->setSafeMode($untrusted);
    // $md->setMarkupEscaped($untrusted);
    $parsed = $md->text($string);

    // $parsed = embed_from_img_tags($parsed);

    // Implmentented reddit's ^ for superscript. Only works one word at a time.
    return preg_replace(
        [
            "/&lt;sup&gt;(.*)&lt;\/sup&gt;/",
            "/\^(\w)/",
            
            // "/<img src=['\"]()['\"])/"
            // "/&lt;a(\s*[='\(\)]*.*)&gt;(.*)&lt;\/a&gt;/",
        ],
        [
            "<sup>$1</sup>",
            "<sup>$1</sup>",

            // "<a$1>$2</a>",
        ],
        $parsed
    );
}

function youtube_embedder(DOMElement $img, DOMDocument $doc){
    $url = $img->getAttribute('src');
    $rawParams = parse_url($url, PHP_URL_QUERY);
    $host = parse_url($url, PHP_URL_HOST);
    $id = "";
    if($rawParams) {
        $params = [];
        parse_str($rawParams, $params);
        $id = $params['v'];
    } else {
        switch($host) {
            case "www.youtu.be":
            case "youtu.be":
                $id = parse_url($url, PHP_URL_PATH);
                if($id[0] == "/") $id = substr($id, 1);
                break;
        }
    }
    $figure = new DOMElement('figure');
    $doc->appendChild($figure);
    $iframe = new DOMElement('iframe');
    $figure->appendChild($iframe);

    $figure->setAttribute('class', 'content-embed content--youtube');

    $iframe->setAttribute('width', '560');
    $iframe->setAttribute('height', '315');
    $iframe->setAttribute('src', 'https://www.youtube.com/embed/'.$id);
    $iframe->setAttribute('title', 'YouTube video player');
    $iframe->setAttribute('frameborder', "0");
    $iframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
    $iframe->setAttribute('allowfullscreen', 'allowfullscreen');
    $img->replaceWith($figure);
    
}

function instagram_embedder(DOMElement $img, DOMDocument $dom) {
    $src = $img->getAttribute("src");
    $figure = new DOMElement('figure');
    $dom->appendChild($figure);
    $iframe = new DOMElement('ig-embed');
    $figure->appendChild($iframe);
    $figure->setAttribute('class', 'content-embed content--instagram');
    $iframe->setAttribute('src', $src); //"https://www.instagram.com/p/$src/?utm_source=ig_embed&amp;utm_campaign=loading");
    $img->replaceWith($figure);
}

/** @deprecated  */
function embed_from_img_tags($html) {
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $imgTags = $dom->getElementsByTagName("img");
    /** @var DOMElement */
    foreach($imgTags as $img) {
        $src = $img->getAttribute('src');
        $host = parse_url($src, PHP_URL_HOST);
        switch($host) {
            case "www.youtube.com":
            case "youtu.be":
            case "www.youtu.be":
            case "youtube.com":
                youtube_embedder($img, $dom);
                break;
            case "instagram.com":
            case "www.instagram.com":
                instagram_embedder($img, $dom);
                break;
        }
    }
    return $dom->saveHTML();
}

function markdown_to_plaintext(?string $string, $stripWhitespace = false) {
    $md = from_markdown($string);
    $md = strip_tags($md);
    if($stripWhitespace) $md = preg_replace("/[\s]/", " ", $md);
    $md = str_replace("\n", "\n\n", $md);
    return trim($md);
}

/**
 * random_string
 *
 * @param  int $length
 * @param  string $string
 * @return string Random string
 */
function random_string($length, $fromChars = null) {
    $validChars = $fromChars ?? "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $min = 0;
    $max = strlen($validChars) - 1;
    $random = "";
    for ($i = 0; $i <= $length; $i++) {
        $random .= $validChars[random_int($min, $max)];
    }
    return $random;
}

function aesthetic_string(string $prefix = "", int $dash_mod = 7) {
    $string = uniqid(true) . microtime();
    $string = (double)bin2hex($string);
    $p = str_replace("=", "", base64_encode(sprintf("%d",($string * 1.27) << 1)));
    if($dash_mod === -1) return $p;
    $pkey = "$prefix";
    $skip = false;
    for($i = strlen($p); $i >= 0; $i--) {
        if($i % $dash_mod === 1) {
            if($skip === false) {
                $i += 1;
                $pkey .= '-';
                $skip = true;
                continue;
            } else {
                $skip = false;
                // $index += 1;
            }
        }
        $pkey .= $p[$i];
    }
    return $pkey;
}


function url_fragment_sanitize(string $value):string {
    $mutant = strtolower($value);
    // Remove any character that isn't alphanumerical and replace it with a dash
    $mutant = preg_replace("/([^a-z0-9])/", "-", $mutant);
    // Remove any consecutive dash
    $mutant = preg_replace("/(-){2,}/", "", $mutant);

    if (!$mutant || $mutant === "-") throw new ValidationIssue("\"$value\" is not suitable to transform into a URL fragment");
    return $mutant;
}

const TIME_TO_READ_WORDS_PER_MINUTE = 200;
const TIME_TO_READ_FORMAT_ROUND = 0;
const TIME_TO_READ_FORMAT_MINSEC = 1;
/**
 * 
 * @param string $string 
 * @param int $output 
 * @return string 
 */
function time_to_read(string $string, int $output = TIME_TO_READ_FORMAT_ROUND) {
    $word_count = str_word_count(strip_tags($string));
    $total_seconds = ($word_count / TIME_TO_READ_WORDS_PER_MINUTE) * 60;
    $minutes = floor($total_seconds / 60);
    $seconds = floor($total_seconds - ($minutes * 60));
    switch($output) {
        case TIME_TO_READ_FORMAT_MINSEC:
            $sec = ($seconds < 10) ? "0$seconds": $seconds;
            return "$minutes:$sec";
        case TIME_TO_READ_FORMAT_ROUND:
        default:
            if($seconds > 30) $minutes += 1;
            if($minutes < 1) $minutes = "~1";
            return "$minutes min";
    }
}

const FAILURE_NOT_A_DATA_URI = -1;
const CONVERT_URI_MAKE_PATH = 0b11111111;
/**
 * Converts a `data:file/mimetype;base64,ai63138b7...` data URI into a file
 * @param string $filename - The location the decoded file should be written to
 * @param string $uri - The URI to be decoded
 * @param int $flags - Also valid are file_put_contents flags: FILE_USE_INCLUDE_PATH, FILE_APPEND, LOCK_EX
 * @return int|false
 */
function convert_data_uri_to_file(string $filename, string $uri, int $flags = 0):int|false {
    if(substr($uri, 0, 5) !== "data:") return FAILURE_NOT_A_DATA_URI;
    // if($flags & CONVERT_URI_MAKE_PATH) {
    //     if(!file_exists($filename)) 
    //     // Let's clean up
    //     $flags -= CONVERT_URI_MAKE_PATH;
    // }
    // Find the base64 portion of the string
    $substr = substr($uri, strpos($uri,",") + 1);
    // Decode the base64
    $decoded = base64_decode(str_replace(' ', '+', $substr));
    // Save it to a file
    $put_result = file_put_contents($filename, $decoded, $flags);
    return $put_result;
}

function is_data_uri($uri):bool {
    if(!is_string($uri)) return false;
    if(substr($uri, 0, 5) === "data:") return true;
    return false;
}

function is_function(mixed $subject):bool {
    if(is_string($subject)) return false;
    return is_callable($subject);
}

function add_target_blank_to_external_links(string $html, string $t = "p"):string {
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput       = true;
    // $dom->loadHTML("<$t>$html</$t>");//.$block['data']['text']."</$tag>");
    $dom->loadHTML(mb_convert_encoding("<$t>$html</$t>", 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR);
    $links = $dom->getElementsByTagName("a");
    if(count($links) === 0) return $html;
    /** @var DOMElement $tag */
    foreach($links as $tag) {
        $href = $tag->getAttribute('href');
        $url = parse_url($href);
        if(key_exists('host', $url) && $url['host'] !== __APP_SETTINGS__['domain_name']) {
            $tag->setAttribute('target', "_blank");
            // $html = preg_replace("/href=[\"']".$href."[\"']/", "href=\"$href\" target=\"blank\"", $html);
        }
    }
    $paragraph = $dom->getElementsByTagName($t);
    $html = "";
    /** @var DOMNode */
    foreach($paragraph as $p) {
        foreach($p->childNodes as $c) {
            $html .= $dom->saveHTML($c);
        }
    }
    return $html ?? ""; 
}

function social_media_links(array $included = []):string {
    $socials = $included;
    if(empty($socials)) $socials = [
        'SocialMedia_email' => __APP_SETTINGS__['SocialMedia_email'],
        'SocialMedia_fediverse' => __APP_SETTINGS__['SocialMedia_fediverse'],
        'SocialMedia_facebook' => __APP_SETTINGS__['SocialMedia_facebook'],
        'SocialMedia_instagram' => __APP_SETTINGS__['SocialMedia_instagram'],
        'SocialMedia_twitter' => __APP_SETTINGS__['SocialMedia_twitter'],
        'SocialMedia_mastodon' => __APP_SETTINGS__['SocialMedia_mastodon'],
    ];
    $social_links = "";
    foreach($socials as $setting => $value) {
        if(!$value) continue;
        $icon = str_replace('SocialMedia_', "", $setting);
        $name = ucwords($icon);
        $social_links .= "<a href=\"$value\" target=\"_blank\" title=\"$name\"><i name=\"$icon\"></i><a>";
    }
    return $social_links;
}