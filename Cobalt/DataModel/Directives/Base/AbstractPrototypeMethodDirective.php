<?php

namespace Cobalt\DataModel\Directives\Base;

use Attribute;
use BadFunctionCallException;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Override;
use stdClass;

// #[Attribute()]
abstract class AbstractPrototypeMethodDirective extends DirectiveCommon {
    function __construct(string $value, protected array $args = []){
        $this->setValue($value);
    }

    /**
     * @param string $value
     * @return void
     */
    #[Override]
    public function setValue(mixed $value): void {
        $this->value = $value;
    }

    /**
     * @return string
     */
    #[Override]
    public function getValue(): mixed {
        if(method_exists($this->type, $this->value)) return $this->type->{$this->value}(...$this->args);
        $model = $this->model;
        if(!$model) $model = $this->type->model;
        if(method_exists($model ?? new stdClass(), $this->value)) return $model->{$this->value}(...$this->args);
        throw new BadFunctionCallException("Method does not exist");
    }

}