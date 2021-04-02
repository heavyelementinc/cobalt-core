<?php
class HelloWorld extends Controllers\ApiController{
    function do_it($ex,$machina){
        return ["Hello World",$_GET['uri']['something'],$machina];
    }
}