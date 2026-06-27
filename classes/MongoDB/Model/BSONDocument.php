<?php

namespace MongoDB\Model\BSONDocument;

use ArrayObject;
use JsonSerializable;
use Override;
use Serializable;

class BSONDocument extends ArrayObject implements JsonSerializable, Serializable {
    #[Override]
    public function jsonSerialize(): mixed
    {
        throw new \Exception('Not implemented');
    }

    function __get($field){ 
        throw new \Exception('Not implemented');
    }

}