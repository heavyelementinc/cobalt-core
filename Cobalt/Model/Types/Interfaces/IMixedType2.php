<?php

namespace Cobalt\Model\Types\Interfaces;

use Cobalt\Model\GenericModel;

interface IMixedType2 {
    /**
     * Checks if this type has been set
     * @return bool 
     */
    function isSet():bool;

    /**
     * Sets the value of this field
     * @param mixed $value
     * @return void 
     */
    public function setValue(mixed $value):void;
    public function getValue():mixed;

    /**
     * Sets the name of this field
     * @param string $value 
     * @return void 
     */
    public function setName(string $value):void;
    public function getName():string;

    /**
     * Sets the parent model of this field, must also
     * set the 'model' field
     * @param GenericModel $model 
     * @return mixed 
     */
    public function setParentModel(GenericModel $model);
    public function getParentModel():GenericModel;

    /**
     * Sets the root model of this field
     * @param GenericModel $model 
     * @return mixed 
     */
    public function setRootModel(GenericModel $model);
    public function getRootModel():GenericModel;

    public function finalInitialization():void;

    public function onUpdate():void;
}