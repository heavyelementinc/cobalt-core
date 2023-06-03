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

    public function getPublicListing() {
        return $this->findAllAsSchema([
            // 'start_time' => ['$lte' => $this->__date()],
            'end_time' => ['$gte' => $this->__date()],
            '$or' => [
                ['advanced.public_index' => 'true', 'published' => true],
                ['advanced.public_index' => 'always']
            ]
        ]);
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

    public function deleteEvent($id) {
        return $this->deleteOne(['_id' => $this->__id($id)])->getDeletedCount();
    }

    private function public_query() {
        return [
            'published' => true,
            'start_time' => ['$lte' => $this->__date()],
            'end_time' => ['$gte' => $this->__date()],
            // '$or' => [
            //     ['end_time' => null],
            // ]
        ];
    }

    public function getAdminWidget() {
        $count = [
            'current' => $this->count($this->public_query()),
            'upcoming' => $this->count([
                'published' => true,
                'start_time' => ['$gte' => $this->__date()],
                'end_time' => ['$gte' => $this->__date()]
            ]),
            'draft' => $this->count([
                'published' => false,
                'end_time' => ['$gte' => $this->__date()]
            ]),
        ];
        return $count;
    }
}
