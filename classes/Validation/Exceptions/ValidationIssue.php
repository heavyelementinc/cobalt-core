<?php

/**
 * ValidationIssue.php - The Validator Issue Exception
 * 
 * Use this method to raise an issue with validating a field's value. This is 
 * useful to be able to raise an issue to the Validator which automatically
 * aggregates ValidationIssue messages so they can be displayed to the client.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2021 Heavy Element, Inc.
 */

namespace Validation\Exceptions;

/**
 * @deprecated use Cobalt\Models\Issues\FilterIssue instead
 * @package Validation\Exceptions
 */
class ValidationIssue extends \Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}
