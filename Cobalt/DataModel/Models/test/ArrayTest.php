<?php

use Cobalt\DataModel\Models\ArrayDebugModel;
use Cobalt\DataModel\Models\BooleanDebugModel;
use PHPUnit\Framework\TestCase;

class ArrayTest extends TestCase {
    private array $arrayOfStrings = [
        'element-1',
        'element-2',
    ];

    private array $arrayOfNumbers = [
        1,4,3,6,7,10
    ];

    private array $arrayEachFilterPass = [
        [
            'field' => 'value'
        ],
        [
            'field' => 'value'
        ]
    ];

    function testArrayFilterPass() {
        $arr = new ArrayDebugModel();
        $value = [
            'min' => $this->arrayOfNumbers, // Has 6 items, should work
            'max' => $this->arrayOfStrings,// Has 2 items, should work
            'ofStrings' => $this->arrayOfStrings,
            'ofNumbers' => $this->arrayOfNumbers,
            'ofModels'  => [
                [
                    'truthy' => "on",
                    'falsey' => "off",
                    'required' => "true"
                ],
                [
                    'truthy' => true,
                    'falsey' => false,
                    'required' => false
                ]
            ]
        ];
        $arr->value = $value;
        $this->assertTrue($arr->min->raw == $this->arrayOfNumbers, "This should match");
        $this->assertTrue($arr->max->raw == $this->arrayOfStrings, "This should match");
        $result = $arr->filterDocument($value);
        $this->assertFalse($result->hasIssues(), "Should not've failed filter test");
        $update = $result->getUpdateDocument();
        $undot = array_undot($update);
        $this->assertTrue($undot['$set'] === $value, "Undotted function doesn't match source");
    }

    function testArrayFilterFail() {
        $arr = new ArrayDebugModel();
        $value = [
            'min' => $this->arrayOfStrings,// Has 2 items, should fail
            'max' => $this->arrayOfNumbers, // Has 6 items, should fail
            'ofStrings' => $this->arrayOfStrings,
            'ofNumbers' => $this->arrayOfNumbers,
            'ofModels'  => [
                [
                    'truthy' => "off",
                    'falsey' => "on",
                    'required' => null
                ],
                [
                    'truthy' => false,
                    'falsey' => true,
                    'required' => null
                ]
            ]
        ];
        $result = $arr->filterDocument($value);
        $this->assertTrue($result->hasIssues(), "Should have failed filter test");
    }
}