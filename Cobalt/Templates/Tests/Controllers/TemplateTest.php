<?php

namespace Cobalt\Templates\Tests\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Templates\Compiler;
use Cobalt\Templates\Tests\CompilerTest;
use MongoDB\Model\BSONDocument;

class TemplateTest extends ModelController {
    public function edit($document): string {
        return "/edit/template.php";
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => false,
            'message' => "Delete this document?",
            'okay' => "Okay",
            'post' => $_POST
        ];
    }

    public static function defineModel(): Model {
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