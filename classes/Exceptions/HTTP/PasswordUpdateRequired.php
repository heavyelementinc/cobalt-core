<?php

/** If the requested resource doesn't exist, we throw a NotFound
 * HTTPException.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Exceptions\HTTP;

class PasswordUpdateRequired extends HTTPException {

    public $status_code = 401;
    public $name = "Password Update Required";

    function __construct($message, $data = []) {
        $data['template'] = "authentication/user-management/password_update.html";
        add_vars(['headline' => $message]);
        parent::__construct($message, $data);
    }
}
