<?php

namespace Cobalt\EventListings\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\EventListings\Models\Event;
use Cobalt\Model\Model;
use MongoDB\Model\BSONDocument;

class Events extends ModelController {
    public static function defineModel(): Model {
        return new Event();
    }

    public function edit($document): string {
        return view("/Cobalt/EventListings/templates/admin/editor copy.php", ['doc' => $document]);
    }

    public function destroy(Model|BSONDocument $document): array {
        return [];
    }

}