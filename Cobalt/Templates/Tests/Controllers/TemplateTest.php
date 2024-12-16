<?php

namespace Cobalt\Templates\Tests\Controllers;

use Cobalt\Controllers\Controller;
use Cobalt\Model\Model;
use Cobalt\Templates\Compiler;
use Cobalt\Templates\Tests\CompilerTest;

class TemplateTest extends Controller {

    public function defineModel(): Model {
        return new CompilerTest();
    }

    function compiler_output() {
        header("Content-Type: text/plain");
        $comp = new Compiler();
        $comp->set_template(__ENV_ROOT__. "/Cobalt/Templates/Tests/Templates/template.php");
        $result = $comp->compile();
        echo $result;
        exit;
    }

    function compiler_render() {
        return view("/Cobalt/Templates/Tests/Templates/template.php", [
            'header' => 'Header Value',
            'doc' => [
                'value' =>  "Some Value"
            ]
        ]);
    }
}