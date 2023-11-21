<?php

namespace Cobalt\Notifications;

use Cobalt\PersistanceMap;
use Cobalt\SchemaPrototypes\ArrayResult;
use Cobalt\SchemaPrototypes\IpResult;
use Cobalt\SchemaPrototypes\PersistableResult;
use Cobalt\SchemaPrototypes\StringResult;

class NotificationActionSchema extends PersistableResult {

    public function __get_schema(): array {
        return [
            'params' => new ArrayResult,
            'context' => new StringResult,
            'route' => [
                new StringResult
            ],
            'path' => new StringResult,
        ];
    }

    function filter($values) {
        $mutant = [];
        foreach($values as $param => $value) {
            switch($param) {
                case "params":
                    $mutant['params'] = $this->validateParams($value);
                    break;
                case "context":
                    $mutant['context'] = $this->validateContext($value);
                    break;
                case "route":
                    $mutant['route'] = $this->validateRoute($value);
                    break;
                case "path":
                    $mutant['path'] = $this->validatePath($value);
                    break;
                default:
                    break;
            }
        }
        return $mutant;
    }

    function validateParams($value) {
        return $value;
    }

    function validateContext($value) {
        return $value;
    }

    function validateRoute($value) {
        return $value;
    }

    function validatePath($value) {
        return $value;
    }

    function __toString(): string {
        $val = $this->getValue();
        $explode = explode('@',$val['route']);
        $result = get_path_from_route($explode[0], $explode[1], $val['params']);
        if(isset($val['route'])) return $result;
        return $val['path'];
    }

}