<?php declare(strict_types=1);

namespace Cobalt\Model;

use ArrayAccess;
use Cobalt\Model\Exceptions\Undefined;
use Cobalt\Model\Traits\Accessible;
use Cobalt\Model\Traits\Schemable;
use Cobalt\Model\Traits\Viewable;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use stdClass;
use Traversable;

abstract class Model extends GenericModel implements Persistable {
    use Accessible;

    /**
     * Specify the schema used by this model
     * @return array{}
     */
    abstract function defineSchema(array $schema = []): array;

    public function bsonSerialize(): array|stdClass|Document {
        return $this->getData();
    }

    public function bsonUnserialize(array $data): void {
        parent::__construct($this->defineSchema());
        $this->setData($data);
    }
}
