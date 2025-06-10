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

use Closure;
use Cobalt\Notifications\Classes\NotificationManager;
use Exceptions\HTTP\BadRequest;
use TypeError;

class ApiHandler extends RequestHandler {
    private $methods_from_stdin = ['POST', 'PUT', 'PATCH', 'DELETE'];
    protected string $content_type = "application/json; charset=utf-8";

    // public $allowed_modes = null;
    public $router_result = null;
    public $update_instructions = [];
    public $events = [];

    public function _stage_init($context_meta):void {
    }

    public function _stage_route_discovered(string $route, array $directives, bool $isOptions):bool {
        /** The request validation is pretty straight-forward, so let's do that */
        $this->request_validation($directives);
        $this->cors_management();
        if(key_exists('headers', $directives)) {
            if($directives['headers'] instanceof Closure == false) throw new TypeError("The headers directive must be an instance of `Closure`");  
            call_user_func($directives['headers'],[$route, $directives]);
        }
        if($isOptions) exit;
        return true;
    }

    public function _stage_execute($router_result):void {
        $this->router_result = $router_result;
        // return $this->router_result;
    }

    public function _stage_output($context_output = ""):mixed {
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

    /**
     * @param HTTPException||Error $e
     */
    public function _public_exception_handler($e):mixed {
        
        // $errorMessage = $e->clientMessage;
        $errorMessage = "Unknown Error";
        if(method_exists($e, "publicMessage")) $errorMessage = $e->publicMessage();
        $this->router_result = [
            'code' => $e->status_code ?? 500, // Why is this $this->status_code
            'error' => $errorMessage,
            'data' => $e->data,
        ];
        if(__APP_SETTINGS__['debug_exceptions_publicly']) $this->router_result['exception'] = $e->getFile() . " on line ". $e->getLine() . ": " . $e->getMessage() . "\n\n". $e->getTraceAsString();
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
                if(getHeader("x-client-ident-set")) {
                    $this->handle_json_post_data($directives, "application/json");
                    break;
                }
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

    function __destruct() {
        // if ($this->_stage_bootstrap['_stage_output'] === true) return;
        // $this->_stage_output();
    }

}
