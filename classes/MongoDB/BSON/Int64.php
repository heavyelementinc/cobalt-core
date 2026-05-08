<?php
namespace MongoDB\BSON;

use Exception;
use Exceptions\HTTP\NotImplemented;
use JsonSerializable;
use Stringable;

final class Int64 implements \MongoDB\BSON\Type, JsonSerializable, Stringable {
    /* Methods */
    final public function __construct(int|string $value) {
        throw new Exception("Not implemented");
    }
    final public function jsonSerialize(): mixed {
        throw new Exception("Not implemented");
    }
    final public function __toString(): string {
        throw new Exception("Not implemented");
    }
}