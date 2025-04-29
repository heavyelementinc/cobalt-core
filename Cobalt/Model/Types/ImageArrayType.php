<?php
namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\OrderedListOfIds;
use Controllers\ClientFSManager;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;
use Validation\Exceptions\ValidationIssue;

class ImageArrayType extends OrderedListOfIds {
    use ClientFSManager;

    public function queryForValues(Model $model, array $ids): ?Cursor {
        return $model->findFiles(['_id' => ['$in' => $ids]],['limit' => count($ids)]);
    }

    public function getModel(): Model {
        return $this->model;
    }

    public function restoreValue(&$value): ?ObjectId {
        // Supporting older formats
        $id = $value['media']['ref'] ?? $value['media']['id'];
        if($id instanceof ObjectId) {
            $ids[] = $id;
        } else {
            return null;
        }
        return $id;
    }

    public function storeValue($id): ObjectId {
        return $id;
    }
    
    function fieldItemTemplate(): string {
        return "Cobalt/Model/templates/types/gallery-item.php";
    }
    
    public function eachSchema() {
        
    }
}