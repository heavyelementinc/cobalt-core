<?php

namespace Components\FAQs\Types;

use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\OrderedListOfForeignIds;
use Components\FAQs\Model\FAQ;
use MongoDB\BSON\ObjectId;

class ArrayOfFAQs extends OrderedListOfForeignIds {
    public function getModel(): Model {
        return new FAQ();
    }

    public function interpretRawValue(&$id): ?ObjectId
    {
        return new ObjectId($id);
    }

    public function storeValue(ObjectId $id): ?ObjectId
    {
        return $id;
    }

    public function fieldItemTemplate(): string {
        throw new \Exception('Not implemented');
    }
}