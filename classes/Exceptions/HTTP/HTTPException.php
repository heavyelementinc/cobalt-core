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
    public $data = [];

    function __construct($message, $data = null, $exit = true) {
        $this->mode = $GLOBALS['route_context'];
        $this->exit = $GLOBALS['allowed_to_exit_on_exception'];
        $this->data = $data;

        // Default to web
        $exe = "web";
        if ($this->mode !== "web") { // If not in the web context
            // Get the app settings
            $mode = __APP_SETTINGS__['context_prefixes'][$GLOBALS['route_context']]['exception_mode'] ?? null;
            if (isset($mode)) $exe = $mode;
            else $exe = "api";
        }
        $this->mode = $exe;
        $header = "HTTP/1.0 " . $this->status_code . " " . $this->name;
        header($header, true, $this->status_code);
        // $this->{$exe . "_execute"}($message, $data);
        parent::__construct($message);
    }

    public function api_execute($message, $data) {
        $GLOBALS['router_result'] = [
            'code' => $this->status_code,
            'error' => $message,
            'data' => $data
        ];
        if ($this->exit) exit;
    }
}
