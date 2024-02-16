<?php

namespace Cobalt\Kernel;

use Exceptions\HTTP\Unauthorized;

abstract class Request {

    public $encoding = "text/html";
    public $permission = null; // Any permission
    public $globals = []; // Variables to be added globally

    abstract function initialize(array $contex):void;
    abstract function route_data(array $route):void;
    abstract function output($router_result):mixed;
    abstract function exception($exception):mixed;
    abstract function settings():array;
    
    public function __initialize($context):void {
        if($this->permission && !has_permission($this->permission)) throw new Unauthorized("User is missing '$this->permission' permission","You don't have permission to be here.");
        add_vars($this->globals);
        $this->initialize($context);
    }

    public function __route_data($route):void {
        $this->route_data($route);
    }

    public function __prepare_output($router_result):mixed {
        return $this->output($router_result);
    }

    public function __exception_handler($exception):mixed {
        return $this->exception($exception);
    }
}