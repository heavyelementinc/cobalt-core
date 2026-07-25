<?php

namespace Cobalt\Model\Types\Interfaces;

use Cobalt\Model\GenericModel;

interface IMixedType {
    public function setName(string $value):void;
    public function getName():string;

    public function setParentModel(GenericModel $model);
    public function getParentModel():GenericModel;

    public function setRootModel(GenericModel $model);
    public function getRootModel():GenericModel;

    public function setValue(mixed $value):void;
    public function getValue():mixed;

    function isSet():bool;

    public function finalInitialization():void;
}