<?php

namespace Cobalt\SchemaPrototypes;

class DateResult extends SchemaResult {
    protected $type = "date";
    
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

}