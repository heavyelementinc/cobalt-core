<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Exceptions\DirectiveDefinitionFailure;
use Cobalt\Model\Attributes\Prototype;
use DateTime;
use DateTimeZone;

class DateType extends MixedType {
    public function initDirectives(): array {
        return [
            'fromEncoding' => 'RFC3339',
            'toEncoding' => 'RFC3339',
        ];
    }

    private function supported_encodings(string $encoding):bool {
        $encodings = ['RFC3339'];
        return in_array($encoding, $encodings);
    }

    #[Directive]
    public function define_fromEncoding(mixed $value, string $name):MixedType {
        if(!$this->supported_encodings($value)) throw new DirectiveDefinitionFailure("$this->name::fromEncoding is not a supported encoding");
        $this->__defineDirective($name, $value);
        return $this;
    }

    #[Directive]
    public function define_toEncoding(mixed $value, string $name):MixedType {
        if(!$this->supported_encodings($value)) throw new DirectiveDefinitionFailure("$this->name::toEncoding is not a supported encoding");
        $this->__defineDirective($name, $value);
        return $this;
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input-date";
        if($tag === null) $tag = "input-date";
        return $this->inputDate($class, $misc, $tag);
    }

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
        if(is_string($value)) {
            $value = new DateTime($value);
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

}