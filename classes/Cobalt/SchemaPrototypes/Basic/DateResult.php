<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationIssue;

class DateResult extends SchemaResult {
    protected $type = "date";
    
    public function getValue():mixed {
        if($this->value instanceof UTCDateTime) return $this->value->toDateTime()->format('c');
        return "";
    }

    public function display():string {
        return $this->format("verbose");
    }

    public function format(string $format = "input"):string {
        $value = $this->getValue();
        $shorthands = [
            'input' => "Y-m-d",
            'default' => 'm/d/Y',
            "verbose" => "l, F jS Y g:i A",
            "long" => "l, F jS Y",
            "12-hour" => "g:i a",
            "24-hour" => "H:i",
            "seconds" => "g:i:s A",
        ];
        if($format === "relative") {
            return "<date-span relative='true' value=\"$value\"></date-span>";
        }
        if(key_exists($format,$shorthands) ) $format = $shorthands[$format];
        if($value instanceof \MongoDB\BSON\UTCDateTime) {
            $dateTime = $value->toDateTime();
            $value = $dateTime->format("U");
            return date($format, $value);
        }
        return date($format, $value / 1000);
    }

    public function filter($value) {
        if(!$value) {
            if($this->__isRequired()) throw new ValidationIssue("A date is required");
            if($this->__isNullable()) return null;
            throw new ValidationContinue("This field is not nullable, we're not doing anything.");
        }
        // $value = new DateTime($value);
        if($value instanceof UTCDateTime) return $value;
        if($value instanceof DateTime) return new UTCDateTime($value->format('u') * 1000);
        $type = gettype($value);
        switch($type) {
            case "string":
                // $date = new DateTime();
                // $date->
                return new UTCDateTime(strtotime($value) * 1000);
            // case "integer":
        }
        throw new ValidationIssue("Unexpected date");
        // if(!in_array($type, ["integer",'double'])) throw new ValidationIssue("Invalid date and time");
        // return new UTCDateTime($value * 1000);
    }

}