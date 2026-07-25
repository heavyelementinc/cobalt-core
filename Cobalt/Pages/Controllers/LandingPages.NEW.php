<?php
namespace Cobalt\Pages\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Pages\Models\PageMap;
use MongoDB\Model\BSONDocument;

class LandingPages extends ModelController {
    public static function defineModel(): Model {
        return new PageMap();
    }

    public function edit($document): string {
        return "/Cobalt/Pages/templates/admin/edit.php";
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Are you sure you want to delete $document->title?",
            'okay' => 'Delete',
            'post' => $_POST,
        ];
    }

}