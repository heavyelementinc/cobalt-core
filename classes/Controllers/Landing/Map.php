<?php

namespace Controllers\Landing;

use Cobalt\Maps\GenericMap;
use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;

class Map extends PersistanceMap {
    public function __get_schema(): array {
        return [
            "h1" => [
                new StringResult,
                'display' => fn ($val) => "<h1>$val</h1>"
            ],
            "title" => [
                new StringResult,
                'display' => fn ($val) => $val
            ],
            "subtitle" => [
                new StringResult,
            ],
            "summary" => [
                new StringResult,
            ],
            "body" => [
                new MarkdownResult,
            ],
            "bio" => [
                new MarkdownResult,
            ],
            "style" => [
                new MarkdownResult,
            ],
            "cta" => [
                new MarkdownResult,
            ],
            "cta_href" => [
                new StringResult,
            ]
        ];
    }
}