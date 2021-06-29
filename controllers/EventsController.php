<?php

use CobaltEvents\EventSchema;

class EventsController {
    function __construct() {
        $this->events = new \CobaltEvents\EventManager();
    }

    function current() {
        $results = $this->events->getCurrent();
        return $results;
        // $toReturn = [];
        // foreach ($results as $result) {
        //     array_push($toReturn, iterator_to_array(new \CobaltEvents\EventSchema($result)));
        // }
        // return $toReturn;
    }

    function list_events() {
        $table = [
            'name' => [
                'header' => 'Event Name (Internal)',
                'display' => fn ($doc) => "<a href='edit/$doc->_id'>$doc->name</a>"
            ],
            'type' => [
                'header' => 'Type'
            ],
            'start_time' => [
                'header' => 'Starts',
                'display' => fn ($doc) => "<date-span format='long' value='" . $doc->{'start_time.raw'} . "'></date-span>"
            ],
            'end_time' => [
                'header' => 'Ends',
                'display' => fn ($doc) => "<date-span format='long' value='" . $doc->{"end_time.raw"} . "'></date-span>"
            ]
        ];
        $result = [];
        $events = $this->events->getEventListing();
        $index = 1;
        foreach ($events as $i => $doc) {
            $event = new \CobaltEvents\EventSchema($doc);
            $result[0] = "<flex-row>";
            $result[$index] = "<flex-row>";
            foreach ($table as $key => $cell) {
                $result[0] .= "<flex-header>$cell[header]</flex-header>";
                $result[$index] .= "<flex-cell>" . (isset($cell['display']) ? $cell['display']($event, $key) : $event->{$key}) . "</flex-cell>";
            }
            $result[0] .= "</flex-row>";
            $result[$index] .= "</flex-row>";
            $index++;
        }

        add_vars([
            'title' => "Cobalt Events",
            'main' => implode("", $result)
        ]);

        set_template("/cobalt_events/index.html");
    }

    function edit_event($id = null) {
        $doc = $this->events->getEventById($id);
        $event = new EventSchema($doc);
        $event->name = "value";

        add_vars([
            'title' => $event->name ?? "Create Event",
            'event' => $event
        ]);

        set_template("/cobalt_events/edit.html");
    }

    function update_event($id = null) {
        $id = $this->events->__id($id);
        $event = new EventSchema();
        $valid = $event->__validate($_POST);
        $result = $this->events->updateOne(
            ['_id' => $id],
            ['$set' => $valid],
            ['upsert' => true]
        );
        // if($result->getModifiedCount() !== 1 && $result->getUpsertedCount() !== 1)
        return array_merge($valid, ['_id' => $id]);
    }
}
