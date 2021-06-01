<?php

/**
 * ValidationFailed.php - The Validator Failed Exception
 * 
 * When the Validate class has finished looping through every field it will move
 * on to the final check where it will determine if any `ValidationIssue`s had
 * been raised or not. If even one issue is raised, then ValidationFailed is
 * thrown.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 Heavy Element, Inc.
 */

namespace Validation\Exceptions;

class ValidationFailed extends \Exceptions\HTTP\BadRequest {
    public $status_code = 422;
    function __construct($message, $data = []) {
        parent::__construct($message);
        $this->data = $data;
    }
}
