<?php
namespace Cobalt\EventListings\Classes;

use Cobalt\Model\Types\DateType;
use DateTime;
use Error;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class CalendarEvent {
    private null|DateTime $start = null;
    private null|DateTime $end = null;
    private string $trigger = "-PT60M";
    private ObjectId $uid;
    private string $name = "";
    private string $description = "";
    private string $location = "";
    private int $priority = 1;
    private string $url = "";
    private string $class = self::CLASS_PUBLIC;

    const CLASS_PUBLIC = "PUBLIC";
    const CLASS_PRIVATE = "PRIVATE";
    const CLASS_CONFIDENTIAL = "CONFIDENTIAL";

    const DATE_FORMAT = "Ymd\THis\Z";
    const OUTLOOK_DATE_FORMAT = "Y-m-d\TH:i:s\Z";
                              //"2025-08-06T02:00:00Z";

    function __construct(DateType|UTCDateTime $start, DateType|UTCDateTime $end, ObjectId $uid) {
        $this->set_uid($uid);
        $this->set_start($start);
        $this->set_end($end);
        // $this->set_name($name);
        // $this->set_description($description);
        // $this->set_location($location);
    }

    function set_uid(ObjectId $uid) {
        $this->uid = $uid;
    }

    function set_start(int|string|DateTime|DateType|UTCDateTime $start) {
        $this->timeToValue($start, $this->start);
    }

    function set_end(int|string|DateTime|DateType|UTCDateTime $end) {
        $this->timeToValue($end, $this->end);
    }
    private function timeToValue(int|string|DateTime|DateType|UTCDateTime $value, &$target) {
        if($value instanceof DateTime) {
            $target = $value;
            return;
        }
        if(is_int($value) || is_string($value)) {
            $target = new DateTime($value);
            return;
        }
        $target = $value->toDateTime();
    }

    function set_name(string $name) {
        $this->name = $name;
    }

    function set_description(string $description) {
        $this->description = strip_tags(str_replace("</p>", "\n\n", $description));
    }

    function set_location(string $location) {
        $this->location = $location;
    }
    
    function set_url(string $url) {
        $this->url = $url;
    }

    /** Specify the number of minutes before an event should trigger a reminder */
    function set_trigger(int $minutes, bool $reminder_before = true) {
        $before = "";
        if($reminder_before === true) {
            $before = "-";
        }
        $this->trigger = $before."PT".$minutes."M";
    }

    function serialize() {
        $startString = $this->start->format(self::DATE_FORMAT);
        $endString   = $this->end->format(self::DATE_FORMAT);
        $created = (new DateTime(date("c",$this->uid->getTimestamp())))->format(self::DATE_FORMAT);
        $description = str_replace("\n","\\n",$this->description) . "\\n\\nLearn more: $this->url";
        return <<<HTML
        BEGIN:VCALENDAR
        VERSION:2.0
        METHOD:PUBLISH
        BEGIN:VEVENT
        DTSTART:$startString
        DTEND:$endString
        LOCATION:$this->location
        URL:$this->url
        TRANSP:OPAQUE
        SEQUENCE:0
        UID:$this->uid
        DTSTAMP:$created
        SUMMARY:$this->name
        DESCRIPTION:$description
        PRIORITY:$this->priority
        CLASS:$this->class
        BEGIN:VALARM
        TRIGGER:$this->trigger
        ACTION:DISPLAY
        END:VALARM
        END:VEVENT
        END:VCALENDAR
        HTML;
    }
    
    function save($location = null) {
        if($location == null) throw new Error("Cannot save to a null location!");
        file_put_contents("$location/$this->name.ics", $this->serialize());
    }
    
    function download() {
        $content = $this->serialize();
        header("Content-type:text/calendar");
        header("Content-type:plaintext");
        header('Content-Disposition: attachment; filename="' . $this->name . '.ics"');
        header('Content-Length: ' . strlen($content));
        header('Connection: close');
        echo $content;
    }

    function googleCalendarLink() {
        $startString = $this->start->format(self::DATE_FORMAT);
        $endString   = $this->end->format(self::DATE_FORMAT);
        return "https://calendar.google.com/calendar/render?".http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->name,
            'details' => trim(substr($this->description, 0, 120))."...\n\nLearn more: $this->url",
            'location' => $this->location,
            'dates' => "$startString/$endString",
            'ctz' => $this->start->getTimezone(),
        ]);
    }

    function outlookLink() {
        return "https://outlook.live.com/owa/?path=/calendar/action/compose&".http_build_query([
            'rru'      => 'addevent',
            'subject'  => $this->name,
            'startdt'  => $this->start->format(self::OUTLOOK_DATE_FORMAT),
            'enddt'    => $this->end->format(self::OUTLOOK_DATE_FORMAT),
            'body'     => trim(substr($this->description, 0, 120))."...\n\nLearn more: $this->url",
            'location' => $this->location,
        ]);
    }
}