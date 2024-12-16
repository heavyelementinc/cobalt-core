<?php

namespace Cobalt\Controllers;

use Cobalt\Model\Model;

abstract class Controller {

    protected Model $model;

    function __construct() {
        $this->__initModel();
    }

    function __initModel() {
        $this->model = $this->defineModel();
    }

    abstract function defineModel():Model;

}