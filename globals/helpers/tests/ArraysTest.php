<?php

use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase {
    function test_is_associative_array() {
        $this->assertTrue(is_associative_array(['test1' => 'test1', 'test2' => 'test2']), "This test should've passed. It was an associative array.");
        $this->assertFalse(is_associative_array([0 => 'test1', 1 => 'test2']), 'This test was NOT an associative array');
        $this->assertFalse(is_associative_array(['0' => 'test1', 1 => 'test2']), 'This *was not* an associative array');
        $this->assertTrue(is_associative_array([0 => 'test1', 2 => 'test2']), 'This was not a list, so it *is*?');
    }
}