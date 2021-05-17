<?php

class MissingPlugin extends \Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}
