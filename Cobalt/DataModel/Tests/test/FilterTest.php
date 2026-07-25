<?php

use Cobalt\DataModel\Classes\Undefined;
use Cobalt\DataModel\Filters\FilterFailed;
use Cobalt\DataModel\Filters\FilterIssue;
use Cobalt\DataModel\Filters\FilterResult;
use Cobalt\DataModel\Tests\ArrayDebugModel;
use Cobalt\DataModel\Tests\BooleanDebugModel;
use Cobalt\DataModel\Tests\ModelDebugNested;
use Cobalt\DataModel\Tests\NumberDebugModel;
use Cobalt\DataModel\Tests\PrimaryDebugModel;
use Cobalt\DataModel\Tests\StringDebugModel;
use Cobalt\DataModel\Types\Generic;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase {
    private array $stringFilterPass = [
        'clearable' => null,
        'clearableFn' => null,
        'min' => "aaaaa",
        'max' => "aaaaa",
        'nullable' => null,
        'pattern' => "aaa",
        'valid' => 'test3',
        'validFromFunction' => 'test1',
    ];

    public function testStringValid() {
        $string = new StringDebugModel();

        $this->fieldFilterPass($string, $this->stringFilterPass, 'stringDocumentValidTest');

        $defaultValue = $string->defaultValue->directives->default->value;
        $this->assertTrue($string->defaultValue->value === $defaultValue, "Default value is wrong");
        $defaultValue = $string->defaultValueFn->directives->default->value;
        $this->assertTrue($string->defaultValueFn->value === $defaultValue, "Default value is wrong");
    }

    public function testStringDocumentPass() {
        // Test the overall filtering
        $string = new StringDebugModel();

        try {
            $filtered = $string->filterDocument($this->stringFilterPass);
            $this->assertTrue(!$filtered->hasIssues(), "There should be no issues.");
        } catch (Exception|Error $e) {
            $this->assertTrue($e instanceof FilterFailed, "Filter failed: ".$e->getMessage());
        }
    }

    private array $stringFilterFails = [
        'min' => "a",
        'max' => "aaaaaa",
        'notNullable' => null,
        // 'nullable' => null,
        'pattern' => "AAA",
        'valid' => 'test4',
        'validFromFunction' => 'test4',
    ];

    public function testStringInvalid() {
        $string = new StringDebugModel();

        $this->fieldFailureTest($string, $this->stringFilterFails, 'stringDocumentInvalidTest');
    }

    public function testStringDocumentFail(){
        $string = new StringDebugModel();
        $result = $string->filterDocument($this->stringFilterFails);
        $this->assertTrue($result->hasIssues() === true, "Filter values should have failed");
    }

    private array $numbersFilterPass = [
        'min' => 2,
        'max' => 9,
        'pattern' => "2e3",
        'allDigitPattern' => 22,
    ];

    public function testNumberDocumentValid() {
        $number = new NumberDebugModel();
        $this->fieldFilterPass($number, $this->numbersFilterPass, 'numberDocumentValidTest');
    }

    private array $numbersFilterFails = [
        'min' => 0,
        'max' => "1e2",
        'pattern' => "1",
        'allDigitPattern' => 2,
    ];

    public function testNumberDocumentInvalid() {
        $number = new NumberDebugModel();
        
        $this->fieldFailureTest($number, $this->numbersFilterFails, 'numberDocumentInvalidTest');
    }

    function testBooleanFilter() {
        $failureValues = [
            'truthy' => '',
            'falsey' => 'true',
            'required' => null,
        ];
        $bool = new BooleanDebugModel();
        $this->fieldFailureTest($bool, $failureValues, 'testBooleanFilter');
    }


    public function testPartialNestedUpdate() {
        $model = new ModelDebugNested();
        $toUpdate = [
            'bools' => ['truthy' => true,],
            'numbers' => ['min' => 4],
            'strings' => ['max' => "one"],
        ];
        $result = $model->filterDocument($toUpdate);
        $arr = $result->getUpdateDocument();
        $undot = array_undot($arr['$set']);
        $this->assertTrue($undot === $toUpdate, "Failed to construct an update object");
    }

    public function testNestedModelValid() {
        $model = new ModelDebugNested();
        /** @var array $valid */
        $valid = $model->strings->valid->directives->valid->normalized();
        $index = array_keys($valid)[1];
        // Set the value of strings->valid to the second $valid option
        $model->strings->valid->value = $index;
        // Then check that `valid`'s display prototype is coming up correct
        $this->assertTrue($model->strings->valid->display() === $valid[array_keys($valid)[1]], "Failed to display valid");

        $arr = [
            'bools' => [
                'truthy' => true,
                'falsey' => '',
                'required' => true
            ],
            'numbers' => $this->numbersFilterPass,
            'strings' => $this->stringFilterPass,
        ];

        $result = $model->filterDocument($arr);
        $this->assertFalse($result->hasIssues(), "`result` should not have issues.");
        $array = [];
        $model->toUpdateQueryArray($array);
    }

    public function testNestedFilterFailure() {
        $model = new ModelDebugNested();
        $arr = [
            'bools' => [
                'truthy' => false,
            ],
            'numbers' => $this->numbersFilterFails,
            'strings' => $this->stringFilterFails,
        ];
        $result = $model->filterDocument($arr);
        $this->assertTrue($result->hasIssues(), "These values should not have passed filtering");
    }

    public function testNestedObjectOverloading() {
        $arr = [
            'bools' => [
                'nested_object' => [
                    'nested_child' => 1
                ]
            ],
            'dictionary' => [
                'number' => 1.8,
                'string' => "two",
                'boolean' => true,
                'nullish' => null
            ]
        ];
        $model = new ModelDebugNested();
        $model->value = $arr;
        $nestingError = "Deep arbitrary dictionary nesting is not working";
        $this->assertTrue($model->bools->nested_object->nested_child->value === $arr['bools']['nested_object']['nested_child'], $nestingError);
        $this->assertTrue($model->dictionary->number->value === $arr['dictionary']['number'], $nestingError);
        $this->assertTrue($model->dictionary->string->value === $arr['dictionary']['string'], $nestingError);
        $this->assertTrue($model->dictionary->boolean->value === $arr['dictionary']['boolean'], $nestingError);
        $this->assertTrue(is_null($model->dictionary->nullish->value), $nestingError);

        $result = $model->filterDocument(['shouldFail' => 'test']);
        $this->assertTrue($result->hasIssues(), "Filtering should have failed");

        // Test to ensure that overloaded values will filter properly.
        $m = new ModelDebugNested();
        $m->value = ['dictionary' => ['number' => 1]];
        $result = $m->filterDocument(['dictionary' => ['number' => 7]]);
        $this->assertFalse($result->hasIssues(), "This should probably(?) be allowed");
    }

    private function fieldFailureTest(Generic $field, array $values, string $testName) {
        foreach($values as $key => $value) {
            /** @var FilterResult $result */
            $field->{$key}->__filter($value);
            $this->assertTrue($field->filterResult->hasIssues(), sprintf("$testName: `$key` filter should not have allowed value `%s`", json_encode($value)));
        }
    }

    private function fieldFilterPass(Generic $field, array $values, string $testName) {
        foreach($values as $key => $value) {
            /** @var FilterResult $result */
            $field->{$key}->__filter($value);
            $this->assertFalse($field->filterResult->hasIssues(), "$testName: FilterResult should have 0 issues!");
            $this->assertTrue($field->{$key}->serialize() === $value, sprintf("$testName: Failed to set $key to %s", json_encode($value)));
        }
    }
}