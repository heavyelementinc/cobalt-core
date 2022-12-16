<?php

use CobaltEvents\EventManager;
use CobaltEvents\EventSchema;
use Exceptions\HTTP\NotFound;

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
                'display' => fn ($doc) => "<a href='/admin/cobalt-events/edit/$doc->_id'>$doc->name</a>"
            ],
            'type' => [
                'header' => 'Type',
                'display' => fn ($doc) => $doc->{"type.display"}
            ],
            'start_time' => [
                'header' => 'Starts',
                'display' => fn ($doc) => $doc->{"start_time.display"}
            ],
            'end_time' => [
                'header' => 'Ends',
                'display' => fn ($doc) => $doc->{"end_time.display"}
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


        add_vars([
            'title' => $event->name ?? "Create Event",
            'event' => $event
        ]);

        set_template("/cobalt_events/edit.html");
    }

    function update_event($ident = null) {
        $id = $this->events->__id($ident);
        $event = new EventSchema();
        $valid = $event->__validate($_POST);
        $result = $this->events->updateOne(
            ['_id' => $id],
            ['$set' => $valid],
            ['upsert' => true]
        );
        // if($result->getModifiedCount() !== 1 && $result->getUpsertedCount() !== 1)
        if (!$ident) header("X-Redirect: /admin/cobalt-events/edit/" . (string)$id);
        return $this->events->findOneAsSchema(['_id' => $id]);
    }

    function delete_event($id) {
        $event = $this->events->getEventById($id);
        if(!$event) throw new NotFound("That event does not exist");
        if(!confirm("Are you sure you want to delete this event?",[])) return;
        header("X-Redirect: /admin/cobalt-events/");
        return $this->events->deleteEvent($id);
    }
}
