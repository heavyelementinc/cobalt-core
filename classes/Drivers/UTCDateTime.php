<?php

/**
 * Database UTCDateTime - Wraps calls to Mongo's DateTime
 * 
 * Meant to provide a way for other developers to build out an SQL backend for 
 * Cobalt Engine since SQL sucks and we currently only support Mongo.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Drivers;

use Validation\Exceptions\ValidationIssue;

class UTCDateTime {

    /**
     * @var MongoDB\BSON\UTCDateTime the timestamp object
     */
    public $timestamp;

    function __construct($date) {
        $this->timestamp = new \MongoDB\BSON\UTCDateTime($this->date($date));
    }

    final private function date($date = null) {
        if ($date === null) return microtime(true) * 1000;
        switch (gettype($date)) {
            case "string":
                return $this->date_string_parse($date);
                break;
            case "integer":
            case "double":
                return $date;
                break;
            case "object":
                if ($date instanceof UTCDateTime) return $this->timestamp;
                break;
        }
        throw new ValidationIssue("Invalid date parameter");
    }


    final private function date_string_parse($date) {
        $timestamp = strtotime($date);
        if ($timestamp) return $timestamp * 1000;
        throw new ValidationIssue("Invalid date parameter");
    }
}
