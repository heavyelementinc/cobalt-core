<?php

use Cobalt\DataModel\Tests\ModelDebugNested;
use Cobalt\DataModel\Types\StringType;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase {
    function test__lookup() {
        $model = new ModelDebugNested();
        $model->strings->defaultValue->value = "test";
        $lookup = $model->__lookup("strings.defaultValue");
        $this->assertTrue($lookup instanceof StringType, "Lookup value must be an instance of StringType");
        $this->assertTrue($lookup->value === "test", "The value of \$lookup should be 'test'");
    }
}