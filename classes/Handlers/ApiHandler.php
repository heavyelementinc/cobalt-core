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

class ApiHandler implements RequestHandler {
    private $methods_from_stdin = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private $content_type = "application/json; charset=utf-8";
    function __construct() {
        $this->http_mode = (is_secure()) ? "https" : "http";
        $this->headers = apache_request_headers();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->allowed_modes = ["https://", "http://"];

        /** This will make the allowed origins be http or https */
        $this->allowed_origins = [];
        foreach (app("API_CORS_allowed_origins") as $el) {
            array_push($this->allowed_origins, $this->url_to_current_mode($el));
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
    }

    public function _stage_output() {
        $return_value = [];
        /** TODO: Finish X-Update-Client-State */
        if (key_exists('X-Update-Client-State', $this->headers)) $return_value = [
            'response' => $this->router_result,
            'settings' => $GLOBALS['app']->public_settings,
            // 'user' => [
            //     'uname' => session('uname')
            // ]
        ];
        else $return_value = $this->router_result;
        /** Echo the result to the output buffer */
        echo json_encode($return_value);
    }

    public function _public_exception_handler($e) {
        $this->router_result = [
            'code' => $this->status_code,
            'error' => $e->getMessage(),
            'data' => $e->data
        ];
        if (!$this->_stage_bootstrap['_stage_output']) $this->_stage_output();
    }

    function request_validation($directives) {
        // if(!isset($GLOBALS['current_route_meta'])) throw new \Exceptions\HTTP\NotFound("404 Not Found");

        /** Handle Cross-Origin Resource Sharing validation */
        $this->cors_management();

        // Check if we need to search for CSRF token in the header.
        if ($this->method !== "GET" && isset($directives) && $directives['csrf_required']) {
            // Check if the X-CSRF-Mitigation token is specified
            if (!key_exists("X-Mitigation", $this->headers)) throw new \Exceptions\HTTP\Unauthorized("Missing CSRF Token");
            if (!\validate_csrf_token($this->headers['X-Mitigation'])) {
                throw new \Exceptions\HTTP\Unauthorized("CSRF Failure");
            }
        }

        /** Check if our request is using a valid method. */
        if (!in_array($this->method, $this->methods_from_stdin)) return;

        $incoming_content_type = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';
        $form_data = "multipart/form-data;";

        /** Check if our content is in JSON format and get it if it is. */
        if ($incoming_content_type === $this->content_type) {
            $incoming_stream = trim(file_get_contents("php://input"));
        }

        /** Now we check if we're getting a file upload and handle it appropriately
         * since we can't use php://input while we're doing this. */
        if (empty($incoming_stream) && strcasecmp(substr($incoming_content_type, 0, strlen($form_data)), $form_data) === 0) {
            $incoming_stream = $_POST['json_payload'];
        }

        /** Throw an error if we haven't recieved any usable data. Do we need
         * to do this? Not sure. */
        if (empty($incoming_stream)) throw new \Exceptions\HTTP\BadRequest("No data was specified.");

        /** Set the $_POST superglobal to equal our incoming JSON. */
        $_POST = json_decode($incoming_stream, true, 512, JSON_THROW_ON_ERROR);
    }

    function cors_management() {
        /** Set our allowed origin to be our app's domain name */
        $allowed_origin = app("domain_name");
        $allowed_methods = "GET, POST, PUT, PATCH, DELETE";

        /** Check if our route allows us to ignore CORS */
        if (isset($GLOBALS['current_route_meta']['cors_disabled']) && $GLOBALS['current_route_meta']['cors_disabled']) {
            /** If it does, we send the origin back to as the allowed origin */
            $allowed_origin = $_SERVER['HTTP_ORIGIN'];
            /** TODO: Send the current route's method back, too. */
        } else if (app("API_CORS_enable_other_origins") && isset($_SERVER['HTTP_ORIGIN'])) {
            /** If HTTP_ORIGIN is set, we'll check if the origin is in our allowed origins and if not,
             * throw an unauthorized error */
            if (!in_array($_SERVER['HTTP_ORIGIN'], $this->allowed_origins)) $this->cors_error();
            /** Otherwise, we'll set our to the server origin, since its allowed */
            $allowed_origin = $_SERVER['HTTP_ORIGIN'];
        }

        $allowed_origin = $this->url_to_current_mode($allowed_origin);

        /** Now we'll throw the headers back to the client */
        header("Access-Control-Allow-Origin: $allowed_origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: $allowed_methods");
        header("Content-Type: " . $this->content_type);
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
        if ($this->_stage_bootstrap['_stage_output'] === true) return;
        $this->_stage_output();
    }

    function url_to_current_mode($url) {
        return "$this->http_mode://" . str_replace($this->allowed_modes, "", $url);
    }

    function execute() {
    }
}
