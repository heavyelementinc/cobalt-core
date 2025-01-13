<?php

use Cobalt\Pages\Classes\PageManager;
use Exceptions\HTTP\Reauthorize;
use Exceptions\HTTP\Unauthorized;
use GuzzleHttp\Exception\GuzzleException;
use Validation\Exceptions\NoValue;

/**
 * Checks if the HTTPS protocol is being used.
 * 
 * @return bool 
 */
function is_secure():bool {
    if(!isset($_SERVER['HTTP_REFERER']) && !isset($_SERVER['HTTP_ORIGIN'])) return __APP_SETTINGS__['session_secure_status'];
    $origin = preg_match('/^https/',$_SERVER['HTTP_ORIGIN'] ?? "");
    $referer = preg_match('/^https/',$_SERVER['HTTP_REFERRER'] ?? "");
    if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === "https") return true;
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $origin || $referer) return true;
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
}


/**
 * Check for confirmation headers and throw an exception if they don't exist
 * 
 * @param string $message confirmation message that the user will see
 * @param array $data data that the confirmation dialog will re-submit
 * @param string $okay the message to "continue"
 * @return bool true if headers exist 
 * @throws Confirm if headers are not detected throw Confirm
 */
function confirm($message, $data, $okay = "Continue", $dangerous = true) {
    try {
        $header = getHeader("X-Confirm-Dangerous");
        if($header) return true;
    } catch (Exception $e) {
        throw new \Exceptions\HTTP\Confirm($message, $data, $okay, $dangerous);
    }
}

/**
 * 
 * @param string $message - Prompt the client will display with the reauth request
 * @param mixed $resubmit - Data the client must return to complete the reauth request
 * @return true         - This function will only ever return true, it will throw an exception in any failure case
 * @throws Unauthorized - If the user is not logged in
 * @throws Reauthorize  - If the user must reauthorize or fails a password verification
 */
function reauthorize($message = "You must re-authroize your account", $resubmit) {
    // Check if session doesn't exist
    if(!session()) throw new Unauthorized("You must be logged in");
    $reauth_session_name = 'last_reauthorized';
    
    try {
        // Check if the X-Reauthorization header is set
        $reauth = getHeader("X-Reauthorization");
    } catch(Exception $e) {
        $reauth = false;
    }

    if($reauth) {
        $password_plain_text = base64_decode($reauth);
        $session_pword = session('pword');
        if(!password_verify($password_plain_text, $session_pword)) throw new Reauthorize($message, $resubmit);
        $_SESSION[$reauth_session_name] = time();
        return true;
    }
    // Check if the session meets the minimum reauth timeline
    if(!isset($_SESSION[$reauth_session_name]) || time() - $_SESSION[$reauth_session_name] >= app("Auth_reauth_timeout")) {
        throw new Reauthorize($message, $resubmit);
    }

    // If everything checks out, return true;
    return true;
}

/**
 * 
 * @param mixed $url 
 * @param string $method 
 * @param array $headers 
 * @param bool $return_headers 
 * @return string|array{body:string, headers:array}
 * @throws GuzzleException 
 * @throws RuntimeException 
 */
function fetch($url, $method = "GET", $headers = [], $return_headers = false) {
    $client = new \GuzzleHttp\Client();
    $request = $client->request($method, $url, [
        'headers' => $headers
    ]);
    $headers = $request->getHeaders();
    $html = $request->getBody()->getContents();
    if (strpos($headers['Content-Type'][0], 'json')) $html = json_decode($html, true);
    if (!$return_headers) return $html;
    return ['body' => $html, 'headers' => $headers];
}

function post_fetch($url, $data, $headers = [], $return_headers = false) {
    $client = new \GuzzleHttp\Client();
    $request = $client->request('POST', $url, [
        'headers' => $headers,
        'form_params' => $data
    ]);
    $html = $request->getBody()->getContents();
    $headers = $request->getHeaders();
    if (strpos($headers['Content-Type'][0], 'json')) $html = json_decode($html, true);
    if (!$return_headers) return $html;
    return ['body' => $html, 'headers' => $headers];
}

function fetch_and_save($url) {
}


function register_individual_post_routes($collection = __APP_SETTINGS__['Posts_collection_name']) {
    $manager = new PageManager(null, $collection);
    $pages = $manager->find($manager->public_query(), ['limit' => 100]);
    $server_name = server_name();

    $html = "";
    foreach($pages as $page) {
        if($page->flags->and($page::FLAGS_EXCLUDE_FROM_SITEMAP)) continue;
        $html .= view("sitemap/url.xml", [
            'location' => $server_name . $page->url_slug->get_path(),
            'lastModified' => $page->body->lastModified(),//$page->live_date->format("Y-m-d"),
            'priority' => 999,
        ]);
    }
    return $html;
}

/**
 * 
 * @param mixed $remote_url 
 * @param mixed $path 
 * @return bool true on success, false on failure
 */
function fetch_remote_file($remote_url, $path):bool {
    $result = copy($remote_url, $path);
    return $result;
    // return file_put_contents($path, $result);

    // $dir            =   $path;
    // $fileName       =   basename($remote_url);
    // $saveFilePath   =   $dir . $fileName;
    // $ch = curl_init($remote_url);
    // $fp = fopen($path, 'wb');
    // curl_setopt($ch, CURLOPT_FILE, $fp);
    // curl_setopt($ch, CURLOPT_HEADER, 0);
    // $result = curl_exec($ch);
    // curl_close($ch);
    // fclose($fp);
    // return $result;

    // //This is the file where we save the information
    // $fp = fopen($path, 'w+');
    // //Here is the file we are downloading, replace spaces with %20
    // $ch = curl_init(str_replace(" ","%20",$remote_url));
    // // make sure to set timeout to a high enough value
    // // if this is too low the download will be interrupted
    // curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    // // write curl response to file
    // curl_setopt($ch, CURLOPT_FILE, $fp); 
    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // // get curl response
    // $result = curl_exec($ch); 
    // curl_close($ch);
    // fclose($fp);

    // return $result;
}


function getHeader($header, $headerList = null, $latest = true, $exception = true) {
    if($headerList === null) $headerList = getallheaders();
    $toMatch = strtolower($header);
    $headers = [];
    foreach($headerList as $key => $value){
        $headers[strtolower($key)] = $value;
    }
    $match = null;
    if(key_exists($toMatch, $headers)) $match = $headers[$toMatch];

    if(gettype($match) === "array" && $latest) return $match[count($match) - 1];
    if($match) return $match;
    if($exception) throw new NoValue("The specified header was not found among the request headers");
    return null;
}


function createJWT(array $header, array $payload, $secret) {
    // Create token header as a JSON string
    $header = json_encode(array_merge([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ],$header));

    // Create token payload as a JSON string
    $payload = json_encode($payload);

    // Encode Header to Base64Url String
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // Encode Payload to Base64Url String
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

    // Encode Signature to Base64Url String
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    return $jwt;
}


/**
 * Will return the $_FILES superglobal to a more sane format:
 * [
 *    [0] => Array
 *        (
 *             [input_name] => 'example',
 *             [name]       => 'example.jpg',
 *             [type]       => 'image/jpeg',
 *             [tmp_name]   => 'tmp/php8830t4',
 *             [error]      => 0,
 *             [size]       => 21509
 *        )
 * ]
 * @return array 
 */
function normalize_file_array() {
    $fileUploadArray = $_FILES;
    $resultingDataStructure = [];
    foreach ($fileUploadArray as $input => $infoArr) {
        $filesByInput = [];
        $nextIndex = count($filesByInput);
        foreach ($infoArr as $key => $valueArr) {
            if (is_array($valueArr)) { // file input "multiple"
                foreach($valueArr as $i=>$value) {
                    $filesByInput[$i][$key] = $value;
                }
                
            }
            else { // -> string, normal file input
                $filesByInput[] = array_merge($infoArr, ['input_name' => $input]);
                break;
            }
        }
        $filesByInput[$nextIndex]['input_name'] = $input;
        $resultingDataStructure = array_merge($resultingDataStructure,$filesByInput);
    }
    $filteredFileArray = [];
    foreach($resultingDataStructure as $file) { // let's filter empty & errors
        if (!$file['error']) $filteredFileArray[] = $file;
    }
    return $filteredFileArray;
}


/**
 * Get the current app's domain name (based on request headers and app settings).
 * If $defaultToAppSetting is true, then this function will always return a value.
 * 
 * @throws Exception if $defatulToAppSetting is true and the incoming server name doesn't exist as the apps domain_name or in the allowed_origins list
 * @return string the domain name of this app (with protocol and NO TRAILING SLASH)
 */
function server_name(bool $defaultToAppSetting = true) {
    $request_from = $_SERVER['SERVER_NAME'];
    $name = "https://$request_from";

    $isSecure = is_secure();
    if($isSecure === false) $name = "http://$request_from";
    
    if($request_from === __APP_SETTINGS__['domain_name']) {
        return $name;
    }
    if(in_array($request_from, __APP_SETTINGS__['API_CORS_allowed_origins'])) {
        return $name;
    }
    if($defaultToAppSetting == false) throw new Exception("Request has no valid server name. Aborting.");
    return ($isSecure) ? "https://".__APP_SETTINGS__['domain_name'] : "http://".__APP_SETTINGS__['domain_name'];
}


function str_to_id($str) {
    $replace = preg_replace("/([^\w])/", "-", $str);
    return strtolower(preg_replace("/(-{2,})/", "-", $replace));
}

function is_bot(?string $useragent = null) {
    if(!$useragent) $useragent = $_SERVER['HTTP_USER_AGENT'];
    if(!isset($useragent)) return false;
    return (preg_match('/bot|crawl|curl|dataprovider|search|get|spider|find|java|majesticsEO|google|yahoo|teoma|contaxe|yandex|libwww-perl|facebookexternalhit|mediapartners/i', $useragent));
}