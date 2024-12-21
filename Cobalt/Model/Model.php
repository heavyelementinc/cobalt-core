<?php declare(strict_types=1);

namespace Cobalt\Model;

use Cobalt\Model\Traits\Accessible;
use Cobalt\Model\Traits\Schemable;
use Cobalt\Model\Traits\Viewable;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use stdClass;

abstract class Model extends GenericModel implements Persistable {
    use Accessible, Schemable;
    
    function __construct() {
        parent::__construct($this->defineSchema(), null);
    }

    /**
     * Specify the schema used by this model
     * @return array{}
     */
    abstract function defineSchema(array $schema = []): array;

    abstract static function __getVersion(): string;

    public function bsonSerialize(): array|stdClass|Document {
        return $this->getData();
    }

    public function bsonUnserialize(array $data): void {
        parent::__construct($this->defineSchema());
        $this->setData($data);
    }
}
