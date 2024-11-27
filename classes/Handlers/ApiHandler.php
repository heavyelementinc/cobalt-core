<?php

/**
 * API Handler
 * 
 * This handler class should contain only that which is needed by the Cobalt
 * engine to handle API calls. What we do here is pretty simple:
 * 
 * Since we know we're in an API context when this method is instantiated, we
 * send the Content-Type header set to JSON. This way, the client expects the
 * content type to be in a JSON format.
 * 
 * We also handle CSRF validation, CORS headers, and data submission validation
 * in this class.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Handlers;

use Cobalt\Notifications\NotificationManager;
use Exceptions\HTTP\BadRequest;

class ApiHandler implements RequestHandler {
    private $methods_from_stdin = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private $content_type = "application/json; charset=utf-8";
    
    public $http_mode = null;
    public $headers = null;
    public $method = null;
    public $allowed_modes = null;
    public $allowed_origins = null;
    public $router_result = null;
    public $_stage_bootstrap = [];
    public $update_instructions = [];
    public $events = [];

    function __construct() {
        $this->http_mode = (is_secure()) ? "https" : "http";
        $this->headers = apache_request_headers();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->allowed_modes = ["https://", "http://"];

        /** This will make the allowed origins be http or https */
        $this->allowed_origins = [];
        foreach (app("API_CORS_allowed_origins") as $el) {
            array_push($this->allowed_origins, $el);
        }
    }


    public function _stage_init($context_meta) {
    }

    public function _stage_route_discovered($route, $directives) {
        /** The request validation is pretty straight-forward, so let's do that */
        return $this->request_validation($directives);
    }

    public function _stage_execute($router_result) {
        $this->router_result = $router_result;
        return $this->router_result;
    }

    public function _stage_output($context_output = "") {
        $return_value = [];

        $return_value = $this->fulfillmentHandling($this->router_result);
        
        $content_type = headers_list()['Content-Type'] ?? headers_list()['content-type'] ?? "";
        if(preg_match("/json/", strtolower($content_type ?? ""))) {
            /** Prepare for API request loading progress bar */
            $json = json_encode($return_value);
            header("Content-Length: " . strlen($json));
        } else if (preg_match('/image/', strtolower($content_type ?? ""))) {
            // exit;
        } else if(gettype($return_value) !== "string") {
            $json = json_encode($return_value);
            if($json === false) $json = json_last_error_msg();
            header("Content-Length: " . strlen($json));
        } else {
            $json = $return_value;
            // header("Content-Length: " . strlen($json));
        }
        
        /** Echo the result to the output buffer */
        return $json;
    }

    private function fulfillmentHandling($router_result) {
        $header = getHeader('X-Include', null, true, false);
        if($header === null) return $router_result;
        
        $include = explode(",",$header);

        $supported_types = ['update', 'events', 'notification', 'settings'];
        $result = [
            'fulfillment' => $router_result,
        ];
        foreach($include as $method) {
            if(!in_array($method, $supported_types)) continue;
            $result[$method] = $this->{$method}();
        }
        return $result;
    }

    private function update() {
        return $this->update_instructions;
    }

    private function events() {
        return [];
    }

    private function settings() {
        return $GLOBALS['app']->public_settings;
    }

    private function notification() {
        $ntfy = new NotificationManager();
        return $ntfy->getUnreadNotificationCountForUser();
    }

    public function _public_exception_handler($e) {
        // $errorMessage = $e->clientMessage;
        $errorMessage = "Unknown Error";
        if(method_exists($e, "publicMessage")) $errorMessage = $e->publicMessage();
        $this->router_result = [
            'code' => $e->status_code ?? 500, // Why is this $this->status_code
            'error' => $errorMessage,
            'data' => $e->data,
        ];
        if(__APP_SETTINGS__['debug_exceptions_publicly']) $this->router_result['exception'] = $e->getMessage();
        if($this->router_result['error'] === "Unknown Error") $this->router_result['error'] = $this->router_result['exception'];
        if (!$this->_stage_bootstrap['_stage_output']) return $this->_stage_output();
    }

    function request_validation($directives) {

        // Handle Cross-Origin Resource Sharing validation
        $this->cors_management();

        // Check if we need to search for CSRF token in the header.
        if ($this->method !== "GET" && isset($directives) && $directives['csrf_required']) {
            // The token can be sent through any one of the following ways,
            // so let's coalesce down to the first available token
            $csrf_token = $_GET[CSRF_INCOMING_FIELD] ?? $_POST[CSRF_INCOMING_FIELD] ?? getHeader(CSRF_INCOMING_HEADER, null, true, false);
            // Check if the token is specified, if not, throw a BadRequest
            if(!$csrf_token) throw new BadRequest("Missing CSRF Token");
            // If it's not valid, we'll throw a BadRequest
            if (csrf_is_valid($csrf_token) === false) throw new BadRequest("CSRF Failure");
        }

        // Check if our request is using a valid method.
        if (!in_array($this->method, $this->methods_from_stdin)) return;

        $incoming_content_type = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';

        $content_type = explode(";",$incoming_content_type)[0];
        // Now let's normalize our submitted data
        switch($content_type) {
            case "application/x-www-form-urlencoded":
            case "x-www-form-urlencoded":
                $this->handle_url_encoded_form_data($directives, $incoming_content_type);
                break;
            case "multipart/form-data":
                $this->handle_multipart_form_data($directives, $incoming_content_type);
                break;
            case "application/json":
            case "application/ld+json":
            // case preg_match("/json/", strtolower($incoming_content_type)) === 1:
                $this->handle_json_post_data($directives, $incoming_content_type);
                break;
            case "application/xml":
            case "text/xml":
            case "application/xhtml+xml":
                $this->handle_xml_post_data($directives, $incoming_content_type);
                break;
            default:
                throw new BadRequest("Unknown Content-Type");
        }
    }

    private function handle_url_encoded_form_data($directives, $incoming_content_type) {
        // Do nothing. PHP handles this contingency on its own.
        return;
    }

    private function handle_json_post_data() {
        $incoming_stream = trim(file_get_contents("php://input"));
        $_POST = json_decode($incoming_stream, true, 512, JSON_THROW_ON_ERROR);
    }

    private function handle_multipart_form_data($directives, $incoming_content_type) {
        $multipart_form_data = "multipart/form-data;";
        $max_upload = getMaximumFileUploadSize();
        if ((int)getHeader('Content-Length') > $max_upload) throw new \Exceptions\HTTP\BadRequest("File upload is too large");
        if (strcasecmp(substr($incoming_content_type, 0, strlen($multipart_form_data)), $multipart_form_data) === 0 && $_POST['json_payload']) {
            $_POST = json_decode($_POST['json_payload'], true, 512, JSON_THROW_ON_ERROR);
        }
    }

    private function handle_xml_post_data($directives, $incoming_content_type) {
        /** @var \SimpleXMLElement */
        $incoming_stream = simplexml_load_file(trim(file_get_contents("php://input", "SimpleXMLElement", LIBXML_NOCDATA)));
        $_POST = (array)$incoming_stream;
    }

    // This might need some refactoring. Is HTTP_ORIGIN where we want to be 
    function cors_management() {
        /** Set our allowed origin to be our app's domain name */
        $allowed_origin = app("domain_name");
        $allowed_methods = "GET, POST, PUT, PATCH, DELETE";
        $current_origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) $current_origin = $this->url_to_current_mode($_SERVER['HTTP_X_FORWARDED_HOST']);
        $current_origin = parse_url($current_origin, PHP_URL_HOST);

        /** Check if our route allows us to ignore CORS */
        if (isset($GLOBALS['current_route_meta']['cors_disabled']) && $GLOBALS['current_route_meta']['cors_disabled']) {
            /** If it does, we send the origin back to as the allowed origin */
            $allowed_origin = $current_origin;
            /** TODO: Send the current route's method back, too. */
        } else if (app("API_CORS_enable_other_origins") && isset($current_origin)) {
            /** If HTTP_ORIGIN is set, we'll check if the origin is in our allowed origins and if not,
             * throw an unauthorized error */
            if (!in_array($current_origin, $this->allowed_origins)) $this->cors_error();
            /** Otherwise, we'll set our to the server origin, since its allowed */
            $allowed_origin = $current_origin;
        }

        $allowed_origin = $this->url_to_current_mode($allowed_origin);

        /** Now we'll throw the headers back to the client */
        header("Access-Control-Allow-Origin: $allowed_origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: $allowed_methods");
        $current_headers = headers_list();
        if(key_exists('Content-Type',$current_headers) || key_exists('content-type', $current_headers)) $this->content_type = $current_headers['Content-Type'] ?? $current_headers['content-type'];
        else header("Content-Type: " . $this->content_type);
    }

    function cors_error() {
        $origin = $this->url_to_current_mode(app('domain_name'));
        /** Throw the domain name back as a CORS header */
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Content-Type: " . $this->content_type);
        /** Throw an unauthorized error */
        throw new \Exceptions\HTTP\Unauthorized("Your origin was not recognized.");
    }

    function __destruct() {
        // if ($this->_stage_bootstrap['_stage_output'] === true) return;
        // $this->_stage_output();
    }

    function url_to_current_mode($url) {
        return "$this->http_mode://" . str_replace($this->allowed_modes, "", $url);
    }
}
