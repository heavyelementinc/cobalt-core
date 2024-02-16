<?php

namespace MongoDB\BSON;

use JsonSerializable;
use Serializable;
use Stringable;

final class Regex implements Serializable, JsonSerializable, Stringable {
    /* Methods */
    final public function __construct(string $pattern, string $flags = "") {

    }
    final public function getFlags(): string {
        return "";
    }
    final public function getPattern(): string {
        return "";
    }
    final public function jsonSerialize(): mixed {
        return "";
    }
    final public function serialize(): string {
        return "";
    }
    final public function __toString(): string {
        return "";
    }
    final public function unserialize(string $data): void {

    }
}