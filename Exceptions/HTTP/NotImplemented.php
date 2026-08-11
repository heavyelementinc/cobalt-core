<?php

/** If the requested resource does not have a controller implemented, we throw a
 * NotImplemented HTTPException.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Exceptions\HTTP;

class NotImplemented extends HTTPException {

    public $status_code = 501;
    public $name = "Not Implemented";

    function __construct($message, $data = []) {
        parent::__construct($message, $data);
    }
}
