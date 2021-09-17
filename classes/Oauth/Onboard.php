<?php

namespace Oauth;

abstract class Onboard extends \Validation\Normalize {

    function __construct($data = null, $normalize_get = true) {
        parent::__construct($data, $normalize_get);
    }

    public function __get_schema(): array {
        return $this->user_normalize();
    }

    /**
     * 
     * @return array 
     */
    abstract protected function user_normalize(): array;

    protected function data_to_user_entry($data) {
        return $this->__validate($data, false);
    }
}
