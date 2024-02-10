<?php

namespace Cobalt\Remote;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\StringResult;

abstract class AuthSchema extends PersistanceMap {

    public function __get_schema(): array {
        $this->__strictDataSubmissionPolicy = false;
        $this->__excludeUnregisteredKeys = false;
        $fields = [
            'clientId' => [
                new StringResult,
                'label' => 'Client ID'
            ],
            'key' => [
                new StringResult,
                'label' => 'API Key'
            ],
            'secret' => [
                new StringResult,
                'label' => 'Secret',
            ],
            'authType' => [
                new StringResult,
                'default' => 'bearer',
                'valid' => [
                    'bearer' => 'Bearer',
                    'param' => 'URL Parameter',
                ],
                'label' => 'Authentication Type'
            ],
            'tokenPrefix' => [
                new StringResult,
                'default' => "Bearer",
                'label' => 'Token Prefix'
            ],
            'paramField' => [
                new StringResult,
                'label' => 'Parameter Field'
            ],
        ];
        $this->auth_fields($fields);
        return $fields;
    }

    abstract function auth_fields(&$fields);

}

