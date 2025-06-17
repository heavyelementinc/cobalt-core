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

/**
 * @deprecated use Cobalt\Models\Issues\FilterSkip instead
 * @package Validation\Exceptions
 */
class NoValue extends \Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}
