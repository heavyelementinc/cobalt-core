<?php

namespace Components\FAQs\Model;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Directives\ValidDirective;
use Cobalt\Model\Model;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\WeakEnumType;

class FAQ extends Model {
    public function defineSchema(array $schema = []): array
    {
        return [
            'summary' => new StringType,
            'body' => new BlockType,
            'live' => new DateType,
            'order' => new NumberType,
        ];
    }

    public function defineController(): ModelController
    {
        throw new \Exception('Not implemented');
    }

    public static function __getVersion(): string
    {
        throw new \Exception('Not implemented');
    }

    public function getCollectionName($string = null): string
    {
        return "CobaltFAQs";
    }

}