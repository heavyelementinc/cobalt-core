<?php

namespace Cobalt\Model\Types;

use ArrayAccess;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\Interfaces\IMixedType2;
use Override;
use Stringable;

class MixedType2 implements Stringable, IMixedType2 {
    const DEFAULT = DIRECTIVE_KEY_DEFAULT;
    const IMMUTABLE = DIRECTIVE_KEY_IMMUTABLE;
    const VALID = DIRECTIVE_KEY_VALID;
    const SKIP_VALIDATION = DIRECTIVE_KEY_SKIP_VALIDATION;
    const FILTER = DIRECTIVE_KEY_FILTER;
    const TYPECAST = DIRECTIVE_KEY_TYPECAST;
    const GET = DIRECTIVE_KEY_GET;
    const SET = DIRECTIVE_KEY_SET;
    protected bool $isSet = false;
    protected mixed $value = null;
    protected string $name = "";
    // protected string $type = "mixed";
    protected string $fieldName = "";
    protected bool $hasModel = false;
    protected GenericModel $model;
    protected GenericModel $rootModel;
    protected GenericModel $parentModel;

    #[Override]
    public function __toString(): string {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function setName(string $value): void {
        $this->{MODEL_RESERVERED_FIELD__FIELDNAME} = $value;
    }

    #[Override]
    public function getName(): string {
        return $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
    }

    #[Override]
    public function setParentModel(GenericModel $model) {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function getParentModel(): GenericModel
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function setRootModel(GenericModel $model)
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function getRootModel(): GenericModel
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function setValue(mixed $value): void
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function getValue(): mixed
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function isSet(): bool
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function finalInitialization(): void
    {
        throw new \Exception('Not implemented');
    }

}