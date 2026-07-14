<?php

namespace Components\ServiceAreas\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Components\ServiceAreas\Models\County;
use Override;
use MongoDB\Model\BSONDocument;

class Counties extends ModelController {
    #[Override]
    public static function defineModel(): Model
    {
        return new County();
    }

    #[Override]
    public function edit($document): string
    {
        return view("Components/ServiceAreas/templates/admin/county-editor.php");
    }

    #[Override]
    public function destroy(Model|BSONDocument $document): array
    {
        return [
            'dangerous' => true,
            'message' => "Delete $document->name?",
            'okay' => 'Okay',
            'post' => $_POST,
        ];
    }

}