<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\Basic\UploadResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Cobalt\SchemaPrototypes\Wrapper\VideoUploadSchema;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use stdClass;

class UploadVideoResult extends UploadResult implements Persistable {
    use Fieldable;

    #[Prototype]
    protected function field(string $class = "", array $misc = [], string $tag = "input"):string {
        $misc = array_merge([
            'accept' => $this->getDirective('accept') ?? '',
        ],$misc);
        return $this->input($class, $misc, $tag);
    }

    #[Prototype]
    protected function embed($embedType = "", array $misc = []) {
        
    }

    public function jsonSerialize(): mixed {
        return $this->originalValue->__dataset;
    }
    
    public function bsonSerialize(): array|stdClass|Document {
        return $this->value->__dataset;
    }

    public function bsonUnserialize(array $data): void {
        $this->value = new VideoUploadSchema($data);
    }
}