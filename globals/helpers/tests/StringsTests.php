<?php

use PHPUnit\Framework\TestCase;

class StringsTests extends TestCase {
    function test_phone_number_serialize_unserialize() {
        $format1 = "(800) 555-5555";
        $canonical = "8005555555";
        $test1 = phone_number_format($canonical, $format1);
        $this->assertTrue($test1 === '(800) 555-5555', "Format failed");
        
        $format2 = "ddd-ddd-dddd";
        $test2 = phone_number_format($canonical, $format2);
        $this->assertTrue($test2 === '800-555-5555', "Format failed");

        $format3 = "ddd.ddd.dddd";
        $test3 = phone_number_format($canonical, $format3);
        $this->assertTrue($test3 === '800.555.5555', "Format failed");

        $this->assertTrue(phone_number_normalize($test1) === $canonical, "Failed to canonicalize format 1");
        $this->assertTrue(phone_number_normalize($test2) === $canonical, "Failed to canonicalize format 2");
        $this->assertTrue(phone_number_normalize($test3) === $canonical, "Failed to canonicalize format 3");
    }

    function test_plural() {
        $this->assertTrue(plural(2) === "s", "Plural should have been s");
        $this->assertTrue(plural(1) === "", "Plural should have been empty");
        $this->assertTrue(plural(4, "plural") === "plural", "Plural should have been true");
        $this->assertTrue(plural(1, singular: "singular") === "singular", "Plural should have been singular");
    }

    // function test_sanitize_path_name($path) {

    // }

    
}