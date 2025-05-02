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
    protected string $operator = '$set';
    public function queryForValues(Model $model, array $ids): ?Cursor {
        return $this->findFiles(['_id' => ['$in' => $ids]], ['limit' => count($ids)]);
    }

    public function getModel(): Model {
        return $this->model;
    }

    public function restoreValue(&$value): ?ObjectId {
        // Supporting older formats
        $id = $value['media']['ref'] ?? $value['media']['id'] ?? $value;
        if($id instanceof ObjectId) {
            return $id;
        } else {
            return new ObjectId($id);
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

    public function queryForObjects(int $limit, int $skip, string $sortField = "_id", int $sortDirection = -1, string $search = "", bool $exclude = true): array {
        $query = ['isThumbnail' => ['$exists' => false]];
        if($exclude) {
            $query['_id'] = ['$nin' => $this->raw];
        }
        $options = ['limit' => $limit, 'skip' => $skip * $limit, 'sort' => [$sortField => $sortDirection]];
        return [
            'cursor' => $this->findFiles($query, $options),
            'count' => $this->fs->count($query, $options)
        ];
    }

    function filter($oids) {
        $filesKey = $this->name;
        if(key_exists($filesKey, $_FILES)) {
            $result = $this->uploadFilesAndGetArrayOfIds($filesKey, ['for' => $this->model->_id ?? null], $_FILES);
            foreach($result as $arr) {
                $oids[] = $arr['_id'];
            }
            $this->operator = '$addToSet';
        }
        return parent::filter($oids);
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        return parent::field($class, $misc, $tag ?? "file-gallery");
    }

    
    public function initDirectives(): array {
        return [
            'operator' => function (&$operators, &$field, &$details) {
                if($this->operator === '$set') {
                    $operators[$this->operator][$field] = $details;
                    return;
                }
                $operators[$this->operator][$this->name] = ['$each' => $details];
            }
        ];
    }
}