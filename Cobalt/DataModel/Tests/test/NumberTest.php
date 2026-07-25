<?php

use Cobalt\DataModel\Tests\NumberDebugModel;
use PHPUnit\Framework\TestCase;

class NumberTest extends TestCase {
    function testNumberPrototypes() {
        $model = new NumberDebugModel();
        $model->min->value = 4;
        $model->min->subtract(2);
        $this->assertTrue($model->min->value == 2, $model->min->value . " sucks");
    }
}