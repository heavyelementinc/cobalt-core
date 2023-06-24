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
        
        $content_type = headers_list()['Content-Type'] ?? headers_list()['content-type'];
        if(preg_match("/json/", strtolower($content_type))) {
            /** Prepare for API request loading progress bar */
            $json = json_encode($return_value);
            header("Content-Length: " . strlen($json));
        } else if (preg_match('/image/', strtolower($content_type))) {
            // exit;
        } else if(gettype($return_value) !== "string") {
            $json = json_encode($return_value);
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
        $this->router_result = [
            'code' => $e->status_code, // Why is this $this->status_code
            'error' => $e->getMessage(),
            'data' => $e->data
        ];
        if (!$this->_stage_bootstrap['_stage_output']) return $this->_stage_output();
    }

    function request_validation($directives) {

        /** Handle Cross-Origin Resource Sharing validation */
        $this->cors_management();
        $file_upload = false;
        # Check if we need to search for CSRF token in the header.
        if ($this->method !== "GET" && isset($directives) && $directives['csrf_required']) {
            # Check if the X-CSRF-Mitigation token is specified
            // if (!key_exists("X-Mitigation", $this->headers)) throw new \Exceptions\HTTP\Unauthorized("Missing CSRF Token");
            // if (!\validate_csrf_token($this->headers['X-Mitigation'])) {
            //     throw new \Exceptions\HTTP\Unauthorized("CSRF Failure");
            // }
        }

        /** Check if our request is using a valid method. */
        if (!in_array($this->method, $this->methods_from_stdin)) return;

        $incoming_content_type = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';

        $is_json = false;

        switch($incoming_content_type) {
            case ($incoming_content_type === $this->content_type):
            case preg_match("/json/", strtolower($incoming_content_type)) === 1:
                $is_json = true;
                break;
        }

        /** Check if our content is in JSON format and get it if it is. */
        if ($is_json) $incoming_stream = trim(file_get_contents("php://input"));

        $multipart_form_data = "multipart/form-data;";
        // $form_data = "Content-Disposition: form-data;";

        /** Now we check if we're getting a file upload and handle it appropriately
         * since we can't use php://input while we're doing this. */
        if (empty($incoming_stream)) {
            $max_upload = getMaximumFileUploadSize();
            if ((int)getHeader('Content-Length') > $max_upload) throw new \Exceptions\HTTP\BadRequest("File upload is too large");
            if (strcasecmp(substr($incoming_content_type, 0, strlen($multipart_form_data)), $multipart_form_data) === 0) {
                $incoming_stream = $_POST['json_payload'];
                $file_upload = true;
            }
        }

        /** Throw an error if we haven't recieved any usable data. Do we need
         * to do this? Not sure. */
        if (empty($incoming_stream) && !$file_upload) throw new \Exceptions\HTTP\BadRequest("No data was specified.");

        /** Set the $_POST superglobal to equal our incoming JSON. */
        if (!empty($incoming_stream)) $_POST = json_decode($incoming_stream, true, 512, JSON_THROW_ON_ERROR);
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
