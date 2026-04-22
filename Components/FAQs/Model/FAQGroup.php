<?php

namespace Components\FAQs\Model;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\StringType;
use Components\FAQs\Types\ArrayOfFAQs;

class FAQGroup extends Model {
    public function defineSchema(array $schema = []): array
    {
        return [
            'name' => new StringType(),
            'faqs' => new ArrayOfFAQs()
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
        throw "CobaltFAQGroups";
    }

}