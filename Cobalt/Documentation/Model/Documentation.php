<?php

namespace Documentation\Model;

use Cobalt\DefinedModel\DefinedModel;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\HexColorType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\StringType;
use DOMDocument;

class Documentation extends DefinedModel {

    public StringType $headline;
    public BlockType $body;
    public ArrayType $tags;
    public ArrayType $includedRoutes;
    public ArrayType $excludedRoutes;
    public HexColorType $color;

    public function initializeField(string $fieldName, MixedType $value): void {
        $this->{$fieldName} = $value;
    }

    public function defineSchema(array $schema = []): array {
        return [];
    }

    public function getCollectionName($string = null): string {
        return "CobaltDocumentation";
    }

    public function modelView($document): string {
        return "";
    }

    public function importStaticDocument(DOMDocument $document) {

    }

}