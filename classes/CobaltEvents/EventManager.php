<?php

namespace CobaltEvents;

class EventManager extends \Drivers\Database {

    private $sort = [
        'start_time' => -1,
        'end_time' => 1
    ];

    public function get_collection_name() {
        return app("CobaltEvents_database_collection");
    }

    public function get_schema_name($doc = []) {
        return "\CobaltEvents\EventSchema";
    }

    public function getEventListing() {
        $result = $this->find(
            [],
            ['limit' => 50, 'sort' => $this->sort]
        );
        return $result;
    }

    public function getCurrent() {
        $pq = $this->public_query();
        $result = $this->find(
            $pq,
            [],
            ['sort' => $this->sort]
        );
        return iterator_to_array($result);
    }

    public function getEventById($id) {
        return $this->findOne(['_id' => $this->__id($id)]);
    }

    private function public_query() {
        return [
            'published' => true,
            'start_time' => ['$lte' => $this->__date()],
            'end_time' => ['$gte' => $this->__date()]
            // '$or' => [
            //     ['end_time' => null],
            // ]
        ];
    }
}
