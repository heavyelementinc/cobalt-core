<?php
namespace Cobalt\Auth\Users\Controllers;

use Cobalt\Auth\Users\Models\User;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use MongoDB\Model\BSONDocument;

class Users extends ModelController {
    public static function defineModel(): Model {
        return new User();
    }

    public function edit($document): string {
        return view("Cobalt/Auth/Users/templates/admin/user-editor.php");
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Are you sure you want to delete <strong>$document->uname</strong>?",
            'okay' => "Yes",
            'post' => $_POST,
        ];
    }

}