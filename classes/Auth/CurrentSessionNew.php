<?php

/**
 * CurrentSession
 * 
 * Manages the session information. This sets the browser cookies and handles the
 * storage, lookup and logging in of sessions.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Auth;

class CurrentSession extends \Drivers\Database {
    function __construct() {
        $this->cookie_name = "PHPSESSIONID";
    }

    public function get_collection_name() {
        return "sessions";
    }
}
