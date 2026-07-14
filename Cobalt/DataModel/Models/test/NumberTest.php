<?php

use Cobalt\DataModel\Models\NumberDebugModel;
use PHPUnit\Framework\TestCase;

class NumberTest extends TestCase {
    function testNumberPrototypes() {
        $model = new NumberDebugModel();
        $model->min->subtract(2);
        $this->assertTrue($model->min->value, $model->min->value . " sucks");
    }
}