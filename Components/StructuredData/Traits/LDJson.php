<?php

namespace Components\StructuredData\Traits;

use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;

trait LDJson {
    public function __toLDJSON():array {
        $serialized = [
            '@context' => "https://schema.org/",
        ];
        foreach($this as $fieldName => $value) {
            self::__serializeToArray($serialized, $fieldName, $value);
        }
        return $serialized;
    }

    static function __serializeToArray(mixed &$type, string $fieldName, MixedType $value) {
        if($value instanceof ArrayType || $value instanceof ModelType) {
            $type[$fieldName] = [];
            foreach($value as $k => $v) {
                self::__serializeToArray($type[$fieldName], $k, $v);
            }
            return;
        }
        $type[$fieldName] = $value->display();
    }
}