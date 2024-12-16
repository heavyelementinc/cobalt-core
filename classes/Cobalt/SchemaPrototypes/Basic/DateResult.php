<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use DateTime;
use MongoDB\BSON\UTCDateTime;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use DateTimeZone;

class DateResult extends SchemaResult {
    use Fieldable;

    protected $type = "date";
    
    public function getValue():mixed {
        $value = $this->value;
        if(is_callable($value)) {
            $value = $value();
        }
        if($value instanceof UTCDateTime) $value = $value->toDateTime();
        if(key_exists("get", $this->schema ?? []) && is_callable($this->schema['get'])) $value = $this->schema['get'];
        return $value;
    }

    public function __toString(): string {
        $val = $this->getValue();
        if(!$val) return "";
        return $val->format('c');
    }

    public function __defaultIndexPresentation(): string {
        $val = $this->getValue();
        if(!$val) return "No date set";
        return $this->relative();
    }

    public function setValue($value):void {
        $this->originalValue = $value;
        if ($value === null) $this->value = $this->schema['default'];
        else if($value instanceof UTCDateTime) $this->value = $value->toDateTime();
        else $this->value = $value;
    }

    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/

    #[Prototype]
    protected function getMilliseconds() {
        $result = ($this->getValue());
        if(!$result) return "0";
        return $result->getTimestamp() * 1000;
    }

    #[Prototype]
    protected function getSeconds() {
        $result = ($this->getValue());
        if(!$result) return "0";
        return floor($result->getTimestamp());
    }

    #[Prototype]
    protected function field($class = "", $misc = []) {
        return $this->inputDate($class, $misc);
    }

    #[Prototype]
    protected function display():string {
        return $this->format("verbose");
    }

    #[Prototype]
    protected function format(string $format = "input"):string {
        $value = $this->getValue();
        if($value === null) return "";
        $shorthands = [
            'iso' => 'c',
            'input' => "Y-m-d",
            'default' => 'm/d/Y',
            "verbose" => "l, F jS Y g:i A",
            "no-dow"  => "F jS Y g:i A",
            "long" => "l, F jS Y",
            "12-hour" => "g:i a",
            "24-hour" => "H:i",
            "seconds" => "g:i:s A",
        ];
        if(key_exists($format,$shorthands) ) $format = $shorthands[$format];
        if($value instanceof \MongoDB\BSON\UTCDateTime) {
            $value = $value->toDateTime();
        }
        if($value instanceof DateTime) {
            $value->setTimezone(new DateTimeZone($_SESSION['timezone'] ?? config()['timezone']));
        }
        // $value = $dateTime->format("U");
        // return date($format, $value);
        return $value->format($format);
    }

    #[Prototype]
    protected function relative($format = "verbose") {
        $date = $this->format("U");
        if(!$date) $date = 0;
        return "<date-span format='$format' relative='true' value='" .($date * 1000). "'></date-span>";
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