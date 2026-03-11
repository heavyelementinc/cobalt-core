<?php

namespace Cobalt\Documentation\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Documentation\Model\Documentation as ModelDocumentation;
use MongoDB\Model\BSONDocument;

class Documentation extends ModelController {

    static $api_read_permission          = "Self";
    static $api_create_permission        = "Documentation_edit";
    static $api_update_permission        = "Documentation_edit";
    static $api_destroy_permission       = "Documentation_destroy";
    static $api_multidestroy_permission  = "Documentation_destroy";
    static $api_batch_archive_permission = "Documentation_destroy";
    static $api_archive_permission       = "Documentation_destroy";
    static $admin_index                  = "Documentation_edit";
    static $admin_new_document           = "Documentation_edit";
    static $admin_edit                   = "Documentation_edit";

    public static function defineModel(): Model {
        return new ModelDocumentation();
    }

    public function edit($document): string {
        return view("/Cobalt/Documentation/templates/admin/documentation-editor.php");
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Are you sure you want to delete $document->title",
            'okay' => "Delete",
            'post' => $_POST,
        ];
    }

}