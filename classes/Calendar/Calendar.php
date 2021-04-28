<?php

namespace Calendar;

class Calendar{
    
    function __construct($date_stamp){
        // $date_stamp = "2021/04/01" or UNIX TIMESTAMP
        
        
    }

    public function draw(){
        return "";
    }

    public function sample(){
        // This method just pulls the example and returns it
        // You'll want to disable this function in the controller and use draw() instead!
        return file_get_contents(__DIR__ . "/example_output.html");
    }
}