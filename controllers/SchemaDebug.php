<?php

use Cobalt\PersistanceMapTest;
use Cobalt\SchemaPrototypes\SchemaResult;
use MongoDB\BSON\UTCDateTime;

class SchemaDebug {
    var $values = [];
    var $test;
    function __construct()
    {
        $this->values = [
            'array' => [
                'test', 
                'value1',
                'super2',
                'no-op'
            ],
            'binary' => 0b1010101010,
            'bool' => true,
            'date' => [
                new UTCDateTime(),
                'from' => 'milliseconds',
                'to' => 'milliseconds'
            ],
        ];
        $this->test = new PersistanceMapTest();
        $this->test->ingest($this->values);
    }

    function filter_test() {
        $this->test->validate($_POST);
        return $this->test->operators();
    }

    function arrayresult() {
        return view("/debug/Prototypes/array.html", [
            'type' => $this->test
        ]);
    }

    function binaryresult() {
        return view('/debug/Prototypes/binary.html', [
            'type' => $this->test
        ]);
    }

    function boolresult() {
        return view('/debug/Prototypes/bool.html', [
            'type' => $this->test
        ]);
    }

    function dateresult() {
        return view('/debug/Prototypes/date.html', [
            'type' => $this->test
        ]);
    }

}