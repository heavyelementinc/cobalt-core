<?php
namespace Cobalt\Model\Types;

use Cobalt\Auth\Users\Models\User;
use Cobalt\Database\Classes\CobaltCursor;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use Cobalt\Model\Types\Abstracts\OrderedListOfForeignIds;
use MongoDB\BSON\ObjectId;

class ArrayOfUsersType extends OrderedListOfForeignIds {
    
    public function getModel(): Model {
        return new User();
    }

    public function interpretRawValue(&$id): ?ObjectId {
        return new ObjectId($id);
    }

    public function storeValue(ObjectId $id): ?ObjectId {
        return $id;
    }

    public function serialize() {
        return $this->raw;
    }

    public function fieldItemTemplate(): string {
        return "Cobalt/Auth/Users/templates/users/object-picker.php";
    }

    /**
     * @param int $limit 
     * @param int $skip 
     * @param string $sortField 
     * @param int $sortDirection 
     * @param string $search 
     * @return array{cursor:?CobaltCursor, count:int}
     */
    public function queryForObjects(int $limit, int $skip, string $sortField = "_id", int $sortDirection = -1, string $search = "", bool $excludeCurrent = true): array {
        // if($search)
        $query = ['$or' => []];
        if($excludeCurrent) {
            $query['_id'] = ['$nin' => $this->raw];
        }
        $options = ['limit' => (int)$limit, 'skip' => (int)$skip, 'sort' => [$sortField => $sortDirection]];
        $model = $this->getModel();
        
        // If permissions directive exists, query for permissions
        if($this->hasDirective('permissions')) {
            $permissions = $this->getDirective('permissions');
            if(!is_array($permissions)) $permissions = [$permissions];
            $query['$or']['permissions'][] = ['$in' => $permissions];
        }
        // If groups directive exists, query for groups
        if($this->hasDirective('groups')) {
            $groups = $this->getDirective('groups');
            if(!is_array($groups)) $groups = [$groups];
            $query['$or']['groups'][] = ['$in' => $groups];
        }
        return [
            'cursor' => $model->find($query, $options),
            'count' => $model->count($query, $options)
        ];
    }

    #[Prototype]
    public function display(): mixed {
        $html = [];
        /** @var User $user */
        foreach($this->value as $user) {
            $html[] = $user->name();
        }
        return join(", ",$html);
    }

}