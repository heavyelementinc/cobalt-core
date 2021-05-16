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

class SharedHandler extends WebHandler {
    private $core_content = __ENV_ROOT__ . "/shared/";
    private $filename = "";
}
