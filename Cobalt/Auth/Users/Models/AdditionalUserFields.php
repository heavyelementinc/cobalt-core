<?php

/** AdditionalUserFields - store more data for each user 
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2026 - Heavy Element, Inc.
 */

namespace Cobalt\Auth\Users\Models;

class AdditionalUserFields {
    
    /**
     * Return an array which conforms to the Normalization Schema Format
     * (/cobalt-core/classes/Validation/readme.md)
     * @return array 
     */
    function __get_additional_schema(): array {
        return [];
    }

    function __get_additional_user_tabs(): array {
        return [];
    }
}
