<?php

/** If the requested resource doesn't exist, we throw a NotFound
 * HTTPException.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Exceptions\HTTP;

class RangeNotSatisfiable extends HTTPException {

    public $status_code = 416;
    public $name = "Range Not Satisfiable";

    function __construct($message, $data = []) {
        parent::__construct($message, $data);
    }
}
