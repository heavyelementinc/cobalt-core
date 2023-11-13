<?php

namespace Exceptions\HTTP;

/**
 * The basis for all Cobalt Engine public HTTP errors.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */
class HTTPException extends \Exception {
    private $mode;
    public $status_code = 500;
    public $data = [
        /*
         
        */
    ];
    public $exit = false;
    public $name = "Unknown Error";
    public $clientMessage = "";

    function __construct($message, $clientMessage = null, $data = null) {
        $this->mode = $GLOBALS['route_context'] ?? "cli";
        $this->clientMessage = $clientMessage;
        if($clientMessage === true) $this->clientMessage = $message;
        $this->exit = $GLOBALS['allowed_to_exit_on_exception'] ?? false;
        $this->data = $data;

        // Check if we're being passed the old way way order and fix it
        if(gettype($clientMessage) === "array" && $data === null) {
            $this->clientMessage = "";
            $this->data = $clientMessage;
        }

        // Default to web
        $exe = "web";
        if ($this->mode !== "web" && $this->mode !== "cli") { // If not in the web context
            // Get the app settings
            $mode = __APP_SETTINGS__['context_prefixes'][$this->mode]['exception_mode'] ?? null;
            if (isset($mode)) $exe = $mode;
            else $exe = "api";
        }
        $this->mode = $exe;
        $header = "HTTP/1.0 " . $this->status_code . " " . $this->name;
        header($header, true, $this->status_code);
        // $this->{$exe . "_execute"}($message, $data);
        parent::__construct($message);
    }

    public function publicMessage() {
        // if(app("debug")) return $this->getMessage();
        $message =  $this->clientMessage;
        if(!$message) $message = $this->name;
        if(!$message) $message = get_class($this);
        return $message;
    }

    public function api_execute($message, $data) {
        $GLOBALS['router_result'] = [
            'code' => $this->status_code,
            'error' => $message,
            'data' => $data
        ];
        if ($this->exit) exit;
    }

    public function dismissError() {
        $this->status_code = 200;
        header("HTTP/1.0 $this->status_code", true);
    }
}
