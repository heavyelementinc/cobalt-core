<?php
class Setup extends \Controllers\Pages{
    function init(){
        add_vars(['title'=>"Welcome to Cobalt"]);
        add_template("/parts/init/form.html");
    }
}