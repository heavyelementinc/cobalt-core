<?php

namespace Cobalt\Notifications;

use Auth\UserCRUD;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\MongoId;

use MongoDB\BSON\ObjectId;

use stdClass;

class NotificationAddresseeResult extends SchemaResult {
    use MongoId;
    protected $original = [];
    public $__hydrated;
    public $__isHydrated = false;

    public function setValue($value):void {
        $this->original = $value;

        $this->addRecipient($value);
    }

    public function getValue():mixed {
        if(isset($this->__isHydrated)) return $this->__hydrated;
        $crud = new UserCRUD();
        $this->__hydrated = $crud->findOne(['_id' => $this->value->id]);
        $this->__isHydrated = true;
        return [
            'id' => $this->value->id,
            'user' => $this->__hydrated,
            'state' => $this->value->state,
        ];
    }

    public function addRecipient(ObjectId|string $value, $seen = false, $read = false) {
        if($value instanceof ObjectId === false) $value = new ObjectId($value);

        $state = 0;
        if($seen) $state = 1;
        if($read) $state = 2;

        $this->value = [
            'id' => $value,
            'state' => $state,
            // 'modified' => new UTCDateTime,
        ];
    }

}