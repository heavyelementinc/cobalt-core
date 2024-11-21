<?php
namespace Cobalt\Templates\Tests;

use Cobalt\Model\Model;

class CompilerTest extends Model {

    public function defineSchema(array $schema = []): array {
        return [

        ];
    }

    public function getCollectionName($string = null): string {
        return "modelTesting";
    }
    
}