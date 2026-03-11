<?php
/** THIS IS A STUB TO SATISFY VSCODE */
namespace MongoDB\BSON;

class Binary {
    const TYPE_USER_DEFINED = "80";
    const int TYPE_GENERIC = 0;
    const int TYPE_FUNCTION = 1;
    const int TYPE_OLD_BINARY = 2;
    const int TYPE_OLD_UUID = 3;
    const int TYPE_UUID = 4;
    const int TYPE_MD5 = 5;
    const int TYPE_ENCRYPTED = 6;
    const int TYPE_COLUMN = 7;
    const int TYPE_SENSITIVE = 8;
    
    final public function getData(): string {
        return "";
    }

    final public function getType():int {
        return 0;
    }

    final public function jsonSerialize():mixed {
        return [];
    }

    final public function __toString():string {
        return "";
    }
}