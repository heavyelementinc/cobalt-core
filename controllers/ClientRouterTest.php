<?php

use Controllers\Controller;

class ClientRouterTest extends Controller{
    function test() {
        add_vars([
            'title' => 'Client Router Test'
        ]);
        return set_template("/debug/client-router-test.html");
    }
}