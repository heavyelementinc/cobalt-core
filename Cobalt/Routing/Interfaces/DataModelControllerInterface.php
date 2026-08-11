<?php

namespace Cobalt\Routing\Interfaces;

use Cobalt\DataModel\Types\DataModel;

abstract class DataModelController implements ControllerInterface {
    abstract function getDataModel():DataModel;
}
