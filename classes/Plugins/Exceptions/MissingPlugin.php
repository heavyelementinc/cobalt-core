<?php

namespace Plugins\Exceptions;

class MissingPlugin extends \Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}
